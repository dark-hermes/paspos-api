<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Copy images from public/images/seeder/products to storage/public/products
        $sourceDir = public_path('images/seeder/products');
        $destDir = storage_path('app/public/products');

        if (!File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        $files = File::files($sourceDir);

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $this->command->info("Copying product image: {$fileName}");
            File::copy($file->getPathname(), $destDir . DIRECTORY_SEPARATOR . $fileName);
        }

        $products = [
            [
                'name' => 'Indomie Goreng',
                'sku' => 'IND-GORENG-001',
                'barcode' => '8996001710213',
                'category_id' => 2,
                'brand_id' => 1,
                'image_path' => 'products/indomie-goreng.png',
                'unit' => 'pcs',
                'description' => 'Mie instan goreng legendaris dengan bumbu rempah khas Indonesia. Praktis disajikan dan menjadi standar rasa mie goreng di pasaran.',
            ],

            [
                'name' => 'Indomie Kari Ayam',
                'sku' => 'IND-KARI-001',
                'barcode' => '8996001710214',
                'category_id' => 2,
                'brand_id' => 1,
                'image_path' => 'products/indomie-kari-ayam.jpg',
                'unit' => 'pcs',
                'description' => 'Mie instan kuah dengan rasa kaldu kari ayam yang kental dan gurih. Dilengkapi dengan minyak bumbu kari untuk aroma yang lebih kuat.',
            ],

            [
                'name' => 'Pepsodent Sensitive Expert Original Toothpaste 120g',
                'sku' => 'PEPS-SEN-EXP-001',
                'barcode' => '8999999301002',
                'category_id' => 6,
                'brand_id' => 2,
                'image_path' => 'products/pepsodent.png',
                'unit' => 'pcs',
                'description' => 'Pasta gigi formulasi khusus untuk meredakan rasa ngilu pada gigi sensitif dengan cepat, sekaligus menjaga kesehatan gusi dan email gigi.',
            ],

            [
                'name' => 'Royco Bumbu Kaldu Rasa Ayam',
                'sku' => 'ROY-BKRA-001',
                'barcode' => '8999999301003',
                'category_id' => 5,
                'brand_id' => 2,
                'image_path' => 'products/royco-ayam.png',
                'unit' => 'pcs',
                'description' => 'Bumbu penyedap masakan serbaguna yang terbuat dari ekstrak daging ayam pilihan. Cocok untuk memperkaya cita rasa hidangan berkuah maupun tumisan.',
            ],

            [
                'name' => 'Royco Bumbu Kaldu Rasa Sapi',
                'sku' => 'ROY-BKRS-001',
                'barcode' => '8999999301004',
                'category_id' => 5,
                'brand_id' => 2,
                'image_path' => 'products/royco-sapi.png',
                'unit' => 'pcs',
                'description' => 'Bumbu penyedap masakan dengan ekstrak sumsum tulang sapi asli. Memberikan rasa kaldu sapi yang mantap dan aroma khas pada setiap masakan.',
            ],

            [
                'name' => 'Kacang Goreng Mpok Nani',
                'sku' => 'KGMN-001',
                'barcode' => null,
                'category_id' => 4,
                'brand_id' => 7,
                'image_path' => 'products/kacang-goreng.jpg',
                'unit' => 'pcs',
                'description' => 'Camilan kacang tanah goreng produksi lokal industri rumahan. Memiliki tekstur renyah dan rasa gurih tradisional tanpa bahan pengawet buatan.',
            ],
        ];

        foreach ($products as $product) {
            Product::query()->create($product);
        }
    }
}
