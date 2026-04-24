<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Copy images from public/images/seeder/brands to storage/public/brands
        $sourceDir = public_path('images/seeder/brands');
        $destDir = storage_path('app/public/brands');

        if (!File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        $files = File::files($sourceDir);

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $this->command->info("Copying brand image: {$fileName}");
            File::copy($file->getPathname(), $destDir . DIRECTORY_SEPARATOR . $fileName);
        }

        $brands = [
            ['name' => 'Indofood', 'logo_path' => 'brands/indofood.png'],
            ['name' => 'Unilever', 'logo_path' => 'brands/unilever.png'],
            ['name' => 'P&G', 'logo_path' => 'brands/pg.png'],
            ['name' => 'Nestle', 'logo_path' => 'brands/nestle.png'],
            ['name' => 'Mayora', 'logo_path' => 'brands/mayora.png'],
            ['name' => 'Wings', 'logo_path' => 'brands/wings.png'],
            ['name' => 'Lainnya',]
        ];

        foreach ($brands as $brand) {
            Brand::query()->create($brand);
        }
    }
}
