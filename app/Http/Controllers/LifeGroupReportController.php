<?php

namespace App\Http\Controllers;

use App\Models\LifeGroup\Attendance;
use App\Models\LifeGroup\LifeGroup;
use App\Models\LifeGroup\Person;
use App\Models\LifeGroup\Session;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LifeGroupReportController extends Controller
{
    /**
     * Report 1: Attendance by Session
     */
    public function attendanceBySession(Request $request)
    {
        $lifeGroupId = $request->get('life_group_id');
        $format = $request->get('format', 'json');

        $query = Session::with(['lifeGroup', 'attendees'])
            ->withCount('attendance');

        if ($lifeGroupId) {
            $query->where('life_group_id', $lifeGroupId);
        }

        $sessions = $query->orderBy('date')->get();

        $headers = ['Red', 'Semana', 'Fecha', 'Estado', 'Asistentes', 'Notas'];
        $rows = $sessions->map(fn($s) => [
            $s->lifeGroup?->name ?? 'N/A',
            "Semana {$s->week_number}",
            $s->date,
            ucfirst($s->status),
            $s->attendance_count,
            $s->notes ?? '-',
        ])->toArray();

        return $this->export($format, 'asistencia-por-sesion', $headers, $rows);
    }

    /**
     * Report 2: Attendance by Leader (logic removed, kept as placeholder)
     */

    /**
     * Report 3: Attendance by Group
     */
    public function attendanceByGroup(Request $request)
    {
        $format = $request->get('format', 'json');

        $groups = LifeGroup::selectRaw('life_groups.name, life_groups.status, COUNT(DISTINCT life_group_sessions.id) as total_sessions, COUNT(life_group_attendances.id) as total_attendance, COUNT(DISTINCT life_group_attendances.person_id) as unique_people')
            ->leftJoin('life_group_sessions', 'life_groups.id', '=', 'life_group_sessions.life_group_id')
            ->leftJoin('life_group_attendances', 'life_group_sessions.id', '=', 'life_group_attendances.session_id')
            ->groupBy('life_groups.id', 'life_groups.name', 'life_groups.status')
            ->orderByDesc('total_attendance')
            ->get();

        $headers = ['Red de Vida', 'Estado', 'Sesiones', 'Total Asistencia', 'Personas Únicas'];
        $rows = $groups->map(fn($g) => [
            $g->name,
            ucfirst($g->status),
            $g->total_sessions,
            $g->total_attendance,
            $g->unique_people,
        ])->toArray();

        return $this->export($format, 'asistencia-por-red', $headers, $rows);
    }

    /**
     * Report 4: New Guests
     */
    public function newGuests(Request $request)
    {
        $format = $request->get('format', 'json');
        $from = $request->get('from');
        $to = $request->get('to');

        $query = Attendance::where('type', 'new_guest')
            ->with(['person', 'session.lifeGroup']);

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $guests = $query->orderByDesc('created_at')->get();

        $headers = ['Invitado', 'Edad', 'Teléfono', 'Red', 'Semana', 'Fecha Registro'];
        $rows = $guests->map(fn($a) => [
            trim(($a->person?->name ?? '') . ' ' . ($a->person?->last_name ?? '')) ?: 'N/A',
            $a->person?->age ?? '-',
            $a->person?->phone ?? '-',
            $a->session?->lifeGroup?->name ?? 'N/A',
            $a->session ? "Semana {$a->session->week_number}" : 'N/A',
            $a->created_at?->format('d/m/Y') ?? '-',
        ])->toArray();

        return $this->export($format, 'nuevos-invitados', $headers, $rows);
    }

    /**
     * Report 5: Recurrent People
     */
    public function recurrentPeople(Request $request)
    {
        $format = $request->get('format', 'json');
        $minSessions = $request->get('min_sessions', 3);

        $people = Person::selectRaw('life_group_people.*, COUNT(life_group_attendances.id) as total_attendance, COUNT(DISTINCT life_group_attendances.session_id) as sessions_attended')
            ->join('life_group_attendances', 'life_group_people.id', '=', 'life_group_attendances.person_id')
            ->groupBy('life_group_people.id')
            ->having('sessions_attended', '>=', $minSessions)
            ->orderByDesc('sessions_attended')
            ->get();

        $headers = ['Nombre', 'Edad', 'Sexo', 'Teléfono', 'Sesiones Asistidas', 'Total Asistencias'];
        $rows = $people->map(fn($p) => [
            trim($p->name . ' ' . $p->last_name) ?: 'N/A',
            $p->age ?? '-',
            $p->gender === 'male' ? 'Masculino' : ($p->gender === 'female' ? 'Femenino' : '-'),
            $p->phone ?? '-',
            $p->sessions_attended,
            $p->total_attendance,
        ])->toArray();

        return $this->export($format, 'personas-recurrentes', $headers, $rows);
    }

    /**
     * Generate export file in the requested format.
     */
    private function export($format, $filename, $headers, $rows)
    {
        switch ($format) {
            case 'csv':
                return $this->exportCsv($filename, $headers, $rows);

            case 'xls':
                return $this->exportExcel($filename, $headers, $rows);

            case 'pdf':
                return $this->exportPdf($filename, $headers, $rows);

            default:
                return response()->json([
                    'headers' => $headers,
                    'rows' => $rows,
                ]);
        }
    }

    /**
     * Export as CSV.
     */
    private function exportCsv($filename, $headers, $rows)
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, $headers);

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ]);
    }

    /**
     * Export as Excel-compatible HTML (opens in Excel).
     */
    private function exportExcel($filename, $headers, $rows)
    {
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="UTF-8"><style>td, th { padding: 4px 8px; border: 1px solid #ccc; } th { background: #1976D2; color: white; }</style></head>';
        $html .= '<body><table>';

        // Header row
        $html .= '<tr>';
        foreach ($headers as $h) {
            $html .= "<th>" . htmlspecialchars($h) . "</th>";
        }
        $html .= '</tr>';

        // Data rows
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= "<td>" . htmlspecialchars((string) $cell) . "</td>";
            }
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}.xls\"",
        ]);
    }

    /**
     * Export as PDF using DomPDF.
     */
    private function exportPdf($filename, $headers, $rows)
    {
        $data = compact('headers', 'rows', 'filename');
        $html = view('reports.table', $data)->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("{$filename}.pdf");
    }
}
