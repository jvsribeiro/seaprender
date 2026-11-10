<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportStudents
{
    private const MAX_ROWS = 200;

    public function __construct(
        private EnrollStudent $enrollStudent,
        private RecordAuditLog $recordAuditLog,
    ) {}

    /**
     * @return array{enrolled: int, skipped: int, errors: list<string>}
     *
     * @throws ValidationException
     */
    public function handle(User $actor, Classroom $classroom, string $path): array
    {
        $rows = $this->readRows($path);
        $result = ['enrolled' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($rows as $line => $row) {
            $validator = Validator::make($row, [
                'nome' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
            ], [], ['nome' => 'nome', 'email' => 'e-mail']);

            if ($validator->fails()) {
                $result['errors'][] = "Linha {$line}: {$validator->errors()->first()}";

                continue;
            }

            try {
                $enrolled = $this->enrollStudent->handle($actor, $classroom, $row['nome'], $row['email']);
            } catch (ValidationException $exception) {
                $result['errors'][] = "Linha {$line}: ".Arr::first(Arr::flatten($exception->errors()));

                continue;
            }

            $result[$enrolled ? 'enrolled' : 'skipped']++;
        }

        $this->recordAuditLog->handle($actor, 'enrollment.imported', $classroom, [
            'enrolled' => $result['enrolled'],
            'skipped' => $result['skipped'],
            'errors' => count($result['errors']),
        ]);

        return $result;
    }

    /**
     * Read the file, keyed by its line number.
     *
     * @return array<int, array{nome: string, email: string}>
     *
     * @throws ValidationException
     */
    private function readRows(string $path): array
    {
        $content = $this->toUtf8((string) file_get_contents($path));
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $delimiter = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';

        $header = array_map(
            fn (?string $column) => Str::of((string) $column)->lower()->replace('-', '')->trim()->toString(),
            str_getcsv($lines[0] ?? '', $delimiter, '"', ''),
        );

        $nameColumn = array_search('nome', $header, true);
        $emailColumn = array_search('email', $header, true);

        if ($nameColumn === false || $emailColumn === false) {
            $this->fail('A primeira linha precisa ter as colunas nome e email.');
        }

        $rows = [];

        foreach (array_slice($lines, 1, preserve_keys: true) as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = str_getcsv($line, $delimiter, '"', '');

            $rows[$index + 1] = [
                'nome' => trim((string) ($columns[$nameColumn] ?? '')),
                'email' => trim((string) ($columns[$emailColumn] ?? '')),
            ];
        }

        if ($rows === []) {
            $this->fail('O arquivo não tem alunos.');
        }

        if (count($rows) > self::MAX_ROWS) {
            $this->fail('Envie no máximo '.self::MAX_ROWS.' alunos por arquivo.');
        }

        return $rows;
    }

    /**
     * Excel on Windows saves CSV files as Windows-1252, with or without a BOM.
     */
    private function toUtf8(string $content): string
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        return mb_check_encoding($content, 'UTF-8')
            ? $content
            : mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['csv' => $message]);
    }
}
