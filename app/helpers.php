<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

function createVerificationCode($length = 10) {
  $characters = '0123456789';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}

function encodeUUID($uuid) {
  return base64_encode(hex2bin(str_replace('-', '', $uuid)));
}

function decodeUUID($shortened) {
  // Step 1: Decode the base64 encoded string
  $binaryData = base64_decode($shortened);

  // Step 2: Convert the binary data to hexadecimal format
  $hexadecimal = bin2hex($binaryData);

  // Step 3: Insert dashes to reconstruct the UUID
  $uuid = substr($hexadecimal, 0, 8) . '-' . substr($hexadecimal, 8, 4) . '-' . substr($hexadecimal, 12, 4) . '-' . substr($hexadecimal, 16, 4) . '-' . substr($hexadecimal, 20);

  return $uuid;
}

function saveBlob($blob, $path, $file_to_delete = null) {
  $d = app()->environment();
  $folder = Carbon::now()->format("Ymd") . "/";
  $name = $d . "/" . $path . $folder . Str::uuid()->getHex()->toString() . '.webp';
  
  $result = Storage::disk('public')->put($name, $blob);

  if ($file_to_delete != null) {
    try {
      Storage::disk('public')->delete($file_to_delete);
    } catch (Exception $e) {
    }
  }
  return $name;
}

function saveS3Blob($blob, string $path, ?string $file_to_delete = null): ?string {
  \Illuminate\Support\Facades\Log::info("saveS3Blob started", ['path' => $path, 'has_blob' => !empty($blob)]);
  $d = app()->environment();
  $folder = Carbon::now()->format("Ymd") . "/";
  $name = $d . "/" . $path . $folder . Str::uuid()->getHex()->toString() . '.webp';
  
  try {
    $isUploaded = Storage::disk('s3')->put($name, $blob);
    
    if (!$isUploaded) {
      \Illuminate\Support\Facades\Log::error("Failed to upload image to S3: {$name}");
      return null;
    }

    if ($file_to_delete) {
      try {
        Storage::disk('s3')->delete($file_to_delete);
      } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning("Failed to delete old S3 image ({$file_to_delete}): " . $e->getMessage());
      }
    }
    return $name;
  } catch (\Throwable $e) {
    \Illuminate\Support\Facades\Log::error("Error in saveS3Blob: " . $e->getMessage());
    return null;
  }
}

function treatImage($blob, int $quality = 80, ?int $maxWidth = 1200, ?int $maxHeight = 1200) {
  return \App\Services\ImageTreatmentService::getInstance()->treat($blob, $quality, $maxWidth, $maxHeight);
}

function awsUrlS3($path, $random = true) {

  if (!empty($path)) {

    $cacheKey = 'aws-url-' . $path;
    $cacheTtl = 5; //60 * 12 * 7; // in minutes
    // Check if the temporary URL is already cached
    if (Cache::has($cacheKey)) {
      return Cache::get($cacheKey);
    }
    //   return Storage::disk('s3')->temporaryUrl($path, Carbon::now()->addMinutes(cacheTtl));
    $temporaryUrl = Storage::disk('s3')->url($path);
    Cache::put($cacheKey, $temporaryUrl, $cacheTtl);
    return $temporaryUrl;
  }

  if ($random) {
    return "https://source.unsplash.com/96x96/daily";
  } else {
    return "";
  }

}

function temporaryUrlS3($path) {

  if ($path) {

    $cacheKey = 'temp-url-' . $path;
    $cacheTtl = 5; // in minutes
    // Check if the temporary URL is already cached
    if (Cache::has($cacheKey)) {
      return Cache::get($cacheKey);
    }
    //   return Storage::disk('s3')->temporaryUrl($path, Carbon::now()->addMinutes(cacheTtl));
    $temporaryUrl = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));
    Cache::put($cacheKey, $temporaryUrl, $cacheTtl);
    return $temporaryUrl;
  }
  return "https://source.unsplash.com/96x96/daily";

}

function deleteS3($path) {
  try {
    $result = Storage::disk('s3')->delete($path);
  } catch (Exception $e) {
  }

  return $result;
}

function copyS3($path, string $newPath) {
  if (empty($path)) {
    return null;
  }

  try {
    $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'webp';
    $d = app()->environment();
    $name = $d . "/" . $newPath . Carbon::now()->format("Ymd") . "/" . Str::uuid()->getHex()->toString() . '.' . $extension;

    $copied = Storage::disk('s3')->copy($path, $name);

    if (!$copied) {
      \Illuminate\Support\Facades\Log::error("Failed to copy S3 image from {$path} to {$name}");
      return null;
    }

    return $name;
  } catch (\Throwable $e) {
    \Illuminate\Support\Facades\Log::error("Error in copyS3: " . $e->getMessage());
    return null;
  }
}

function saveAmazonFile($file, $path, $old_file = null) {
  $d = app()->environment();
  $extension = $file->extension();
  $full_name = $d . $path . "/" . uniqid() . "." . $extension;
  // https://www.positronx.io/laravel-image-resize-upload-with-intervention-image-package/
  // https://laracasts.com/discuss/channels/laravel/resize-an-image-before-upload-to-s3
  $manager = new ImageManager(new Driver());
  $img = $manager->read($file);
  $encoded = $img->encodeByExtension($extension);

  $path = Storage::disk('s3')->put($full_name, (string) $encoded);

  if ($old_file != null) {
    try {
      Storage::disk('s3')->delete($old_file);
    } catch (Exception $e) {
    }
  }
  return $full_name;
}

function queryServerSide($request, $query) {
  if ($request->has('sortBy')) {
    $sortBy = $request->get('sortBy');
    $sortDesc = $request->get('sortDesc');
    foreach ($sortBy as $key => $value) {
      $sortBy_ = $sortBy[$key];
      $sortDesc_ = $sortDesc[$key] == 'true' ? 'desc' : 'asc';

      $query->orderBy($sortBy_, $sortDesc_);
    }
  }

  return $query;
}

function replace_tags($string, $tags, $force_lower = false) {
  return preg_replace_callback('/\\{\\{([^{}]+)\}\\}/',
    function ($matches) use ($force_lower, $tags) {
      $key = $force_lower ? strtolower($matches[1]) : $matches[1];
      return array_key_exists($key, $tags)
      ? $tags[$key]
      : ''
      ;
    }
    , $string);
}
