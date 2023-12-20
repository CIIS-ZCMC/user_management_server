<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use League\Csv\Reader;

use App\Models\SalaryGrade;
use Illuminate\Support\Facades\File;

class SalaryGradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = base_path('salary_grade_2023.csv');

        // Read the CSV file
        $csvData = $this->readCsv($csvFile);

        // Insert data into the 'salary_grades' table
        $this->insertData($csvData);
    }
    
    private function readCsv($file)
    {
        // Ensure the file exists
        if (!File::exists($file)) {
            $this->command->error("CSV file not found: $file");
            return [];
        }

        // Use Laravel's CsvReader for better CSV parsing
        $csv = Reader::createFromPath($file, 'r');
        $csv->setHeaderOffset(0); // Assumes the first row is the header

        return iterator_to_array($csv->getRecords());
    }

    private function insertData($data)
    {
        foreach ($data as $row) {
            if (count($row) !== 10) {
                $this->command->error('Invalid row format. Skipping row: ' . implode(', ', $row));
                continue;
            }

            try {
                SalaryGrade::create([
                    'salary_grade_number' => $row['salary_grade_number'] ?? null,
                    'one' => $row['one'] ?? null,
                    'two' => $row['two'] ?? null,
                    'three' => $row['three'] ?? null,
                    'four' => $row['four'] ?? null,
                    'five' => $row['five'] ?? null,
                    'six' => $row['six'] ?? null,
                    'seven' => $row['seven'] ?? null,
                    'eight' => $row['eight'] ?? null,
                    'tranch' => $row['tranch'] ?? null,
                    'effective_at' => now(),
                ]);
            } catch (\Exception $e) {
                $this->command->error('Error inserting row: ' . implode(', ', $row));
                $this->command->error($e->getMessage());
            }
        }
    }
}