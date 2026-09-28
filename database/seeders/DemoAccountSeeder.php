<?php

namespace Database\Seeders;

use App\Enums\FarmerApprovalStatus;
use App\Enums\ProductAvailability;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\ProductCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        // admins Dallas office mein (Contact page wali jagah)
        $officeContact = ['address' => '2100 Ross Ave, Dallas, TX 75201', 'latitude' => 32.7880, 'longitude' => -96.8000];

        $admin = $this->createUser('Admin Team', 'admin@marketlink.test', 'Admin@1234', UserRole::Admin, [
            ...$officeContact, 'phone' => '(214) 555-0100',
        ]);
        $admin->syncRoles('super-admin');

        // demo ke liye Support Admin - sirf farmers / customers / moderation, markets, categories, announcements, reports nahi
        $supportAdmin = $this->createUser('Support Team', 'support@marketlink.test', 'Support@1234', UserRole::Admin, [
            ...$officeContact, 'phone' => '(214) 555-0112',
        ]);
        $supportAdmin->syncRoles('support-admin');

        // community moderator ka demo account - sirf posts approve / reject
        $communityModerator = $this->createUser('Community Team', 'community-mod@marketlink.test', 'Community@1234', UserRole::Admin, [
            ...$officeContact, 'phone' => '(214) 555-0198',
        ]);
        $communityModerator->syncRoles('community-moderator');

        foreach ($this->demoFarmers() as $farmerDetails) {
            $this->createFarmerWithProducts($farmerDetails);
        }

        // Dallas, Houston aur Austin mein ek ek customer, taake "nearest market" sab ke liye alag dikhe
        $this->createUser('Sara Ahmed', 'sara@marketlink.test', 'Customer@1234', UserRole::Customer, [
            'phone' => '(214) 555-0123', 'address' => '4515 McKinney Ave, Dallas, TX 75205', 'latitude' => 32.8210, 'longitude' => -96.7930,
        ]);
        $this->createUser('Bilal Khan', 'bilal@marketlink.test', 'Customer@1234', UserRole::Customer, [
            'phone' => '(713) 555-0145', 'address' => '1920 Westheimer Rd, Houston, TX 77098', 'latitude' => 29.7440, 'longitude' => -95.4000,
        ]);
        $this->createUser('Ayesha Raza', 'ayesha@marketlink.test', 'Customer@1234', UserRole::Customer, [
            'phone' => '(512) 555-0167', 'address' => '1100 S Lamar Blvd, Austin, TX 78704', 'latitude' => 30.2530, 'longitude' => -97.7640,
        ]);

        // create nahi updateOrCreate - seeder dobara chalne pe "Mango season" announcement double ho raha tha
        Announcement::updateOrCreate(
            ['title' => 'Mango season is almost over'],
            [
                'created_by_user_id' => $admin->id,
                'body' => 'This is the last month for Rio Grande Valley mangoes at most stalls. Reserve early before the weekend.',
                'audience' => 'customers',
                'published_at' => now(),
            ]
        );
    }

    // $contactDetails mein phone / address / latitude / longitude, koi bhi chhoda ja sakta hai
    private function createUser(string $name, string $email, string $plainPassword, UserRole $role, array $contactDetails = []): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $plainPassword,
                'role' => $role,
                'phone' => $contactDetails['phone'] ?? null,
                'address' => $contactDetails['address'] ?? null,
                'latitude' => $contactDetails['latitude'] ?? null,
                'longitude' => $contactDetails['longitude'] ?? null,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }

    private function createFarmerWithProducts(array $farmerDetails): void
    {
        $farmerUser = $this->createUser(
            $farmerDetails['contact_person'],
            $farmerDetails['email'],
            'Farmer@1234',
            UserRole::Farmer,
            [
                'phone' => $farmerDetails['phone'],
                'address' => $farmerDetails['address'],
                'latitude' => $farmerDetails['latitude'],
                'longitude' => $farmerDetails['longitude'],
            ],
        );

        $isApproved = $farmerDetails['status'] === FarmerApprovalStatus::Approved;

        // sirf 'urgent_orders' wale farmers urgent lete hain. Hours 00:00-23:59 taake demo kabhi bhi dikh sake
        $urgentOrderSettings = $farmerDetails['urgent_orders'] ?? null;

        // pichle kuch mahinon mein phaila diya taake "New stall" sirf asal naye stall pe aaye
        $joinedAt = now()->subMonths($farmerDetails['joined_months_ago']);

        $farmerProfile = FarmerProfile::updateOrCreate(
            ['user_id' => $farmerUser->id],
            [
                'stall_name' => $farmerDetails['stall_name'],
                'slug' => Str::slug($farmerDetails['stall_name']),
                'contact_person' => $farmerDetails['contact_person'],
                'bio' => $farmerDetails['bio'],
                'address' => $farmerDetails['address'],
                'latitude' => $farmerDetails['latitude'],
                'longitude' => $farmerDetails['longitude'],
                'order_cutoff_hours' => 12,
                'accepts_urgent_orders' => $urgentOrderSettings !== null,
                'ai_auto_confirms_urgent' => $urgentOrderSettings['ai_auto_confirms'] ?? false,
                'ai_marks_urgent_ready' => $urgentOrderSettings['ai_marks_ready'] ?? false,
                'urgent_prep_minutes' => $urgentOrderSettings['prep_minutes'] ?? 10,
                'urgent_pickup_starts_at' => $urgentOrderSettings ? '00:00' : null,
                'urgent_pickup_ends_at' => $urgentOrderSettings ? '23:59' : null,
                'max_urgent_orders_per_hour' => 5,
                'approval_status' => $farmerDetails['status'],
                'approved_at' => $isApproved ? $joinedAt->copy()->addDays(3) : null,
                'cover_image_path' => $farmerDetails['cover_image'],
            ],
        );

        // created_at mass assignable nahi, is liye account aur stall dono pe alag se
        $farmerUser->forceFill(['created_at' => $joinedAt])->save();
        $farmerProfile->forceFill(['created_at' => $joinedAt])->save();

        foreach ($farmerDetails['markets'] as $marketSlug => $stallNumber) {
            $market = Market::where('slug', $marketSlug)->firstOrFail();

            $farmerProfile->markets()->syncWithoutDetaching([$market->id => ['stall_number' => $stallNumber]]);

            // market ke har khule din ek pickup slot
            foreach ($market->operating_days as $dayName) {
                $farmerProfile->pickupWindows()->updateOrCreate(
                    [
                        'market_id' => $market->id,
                        'day_of_week' => $this->dayNameToNumber($dayName),
                    ],
                    [
                        'starts_at' => $market->opens_at,
                        'ends_at' => Carbon::parse($market->opens_at)->addHours(3)->format('H:i'),
                        'max_orders' => 15,
                    ],
                );
            }
        }

        foreach ($farmerDetails['products'] as $productDetails) {
            [$productName, $categorySlug, $price, $unit, $stockQuantity, $imageFile, $description] = $productDetails;

            $farmerProfile->products()->updateOrCreate(
                ['slug' => Str::slug($productName.' '.$farmerProfile->stall_name)],
                [
                    'product_category_id' => ProductCategory::where('slug', $categorySlug)->value('id'),
                    'name' => $productName,
                    'description' => $description,
                    'price' => $price,
                    'unit' => $unit,
                    'stock_quantity' => $stockQuantity,
                    'weekly_default_quantity' => max($stockQuantity, 20),
                    'availability' => $stockQuantity > 0 ? ProductAvailability::Available : ProductAvailability::SoldOut,
                    'image_path' => $imageFile ? 'images/products/'.$imageFile : null,
                ],
            );
        }
    }

    private function dayNameToNumber(string $dayName): int
    {
        return array_search(strtolower($dayName), ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
    }

    // product = [name, category slug, price dollars mein, unit, stock, image file, description].
    // image null ho to Product::imageUrl() category ki photo dikhata hai, farmer ka cover_image bhi aise hi.
    // har active market ke paas kam az kam ek approved farmer, address asli qareebi towns ke
    private function demoFarmers(): array
    {
        return [
            [
                'stall_name' => 'Green Valley Farm',
                'contact_person' => 'Imran Hussain',
                'email' => 'greenvalley@marketlink.test',
                'phone' => '(972) 555-0101',
                'bio' => 'Family-run vegetable farm outside Wylie, growing without chemical sprays since 2015.',
                'address' => 'Wylie, TX 75098',
                'latitude' => 33.0151,
                'longitude' => -96.5389,
                'cover_image' => 'images/farmers/green-valley.webp',
                'status' => FarmerApprovalStatus::Approved,
                'joined_months_ago' => 8,
                // "AI foran confirm karta hai" wala urgent flow, aur 2 min baad ready - demo mein jaldi dikhe
                'urgent_orders' => ['ai_auto_confirms' => true, 'ai_marks_ready' => true, 'prep_minutes' => 2],
                'markets' => ['dallas-farmers-market' => 'A-12', 'cowtown-farmers-market' => 'B-03'],
                'products' => [
                    ['Vine tomatoes', 'vegetables', 4.50, 'kg', 40, 'vine-tomatoes.webp', 'Picked the morning before market day, still on the vine.'],
                    ['Spinach', 'vegetables', 2.50, 'bunch', 25, 'spinach.webp', 'Tender leaves, washed and bundled.'],
                    ['Carrots', 'vegetables', 3.00, 'kg', 30, 'carrots.webp', 'Sweet winter-variety carrots.'],
                    ['Cucumbers', 'vegetables', 3.50, 'kg', 4, 'cucumbers.webp', 'Crunchy pickling cucumbers, great for salad too.'],
                    ['Fresh mint', 'herbs', 2.00, 'bunch', 30, 'mint.webp', 'Strong, fragrant mint for sauces and drinks.'],
                ],
            ],
            [
                'stall_name' => 'Sunrise Orchard',
                'contact_person' => 'Nadia Memon',
                'email' => 'sunrise@marketlink.test',
                'phone' => '(956) 555-0202',
                'bio' => 'Orchard in the Rio Grande Valley growing mangoes, guava and other warm-weather fruit.',
                'address' => 'Mission, TX 78572',
                'latitude' => 26.2159,
                'longitude' => -98.3253,
                'cover_image' => 'images/farmers/sunrise-orchard.webp',
                'status' => FarmerApprovalStatus::Approved,
                'joined_months_ago' => 6,
                'markets' => ['houston-farmers-market' => 'A-04', 'pearl-farmers-market' => 'P-10'],
                'products' => [
                    ['Valley mangoes', 'fruits', 5.00, 'kg', 15, 'mangoes.webp', 'End of season Rio Grande Valley mangoes, very sweet.'],
                    ['Bananas', 'fruits', 2.50, 'dozen', 20, 'bananas.webp', 'Naturally ripened, never gassed.'],
                    ['Guava', 'fruits', 4.00, 'kg', 25, null, 'Pink-flesh guava from our own trees.'],
                    ['Pomegranates', 'fruits', 6.00, 'kg', 0, 'pomegranates.webp', 'Juicy red pomegranates.'],
                ],
            ],
            [
                'stall_name' => 'Desi Dairy Corner',
                'contact_person' => 'Kashif Ali',
                'email' => 'desidairy@marketlink.test',
                'phone' => '(281) 555-0303',
                'bio' => 'Small dairy near Katy with grass-fed cows and free-range hens.',
                'address' => 'Katy, TX 77494',
                'latitude' => 29.7858,
                'longitude' => -95.8245,
                'cover_image' => 'images/farmers/desi-dairy.webp',
                'status' => FarmerApprovalStatus::Approved,
                'joined_months_ago' => 5,
                'markets' => ['houston-farmers-market' => 'C-02', 'sfc-farmers-market-austin' => 'D-07'],
                'products' => [
                    ['Fresh cow milk', 'dairy-eggs', 4.00, 'litre', 40, 'milk.webp', 'Milked the same morning, kept chilled.'],
                    ['Free-range eggs', 'dairy-eggs', 6.50, 'dozen', 18, 'eggs.webp', 'Brown eggs from hens that roam outside.'],
                    ['Homemade yogurt', 'dairy-eggs', 5.00, 'kg', 12, 'yogurt.webp', 'Thick set yogurt in clay pots.'],
                    ['Desi butter', 'dairy-eggs', 7.00, 'piece', 3, 'butter.webp', 'Half-pound block of hand-churned butter.'],
                ],
            ],
            [
                'stall_name' => 'Crust and Crumb',
                'contact_person' => 'Hina Siddiqui',
                'email' => 'crustcrumb@marketlink.test',
                'phone' => '(817) 555-0404',
                'bio' => 'Home bakery in Fort Worth making sourdough and whole wheat bread in small batches every weekend.',
                'address' => 'Fort Worth, TX 76107',
                'latitude' => 32.7488,
                'longitude' => -97.3600,
                'cover_image' => 'images/farmers/crust-crumb.webp',
                'status' => FarmerApprovalStatus::Approved,
                'joined_months_ago' => 4,
                // manual urgent flow - farmer khud accept karta hai
                'urgent_orders' => ['ai_auto_confirms' => false],
                'markets' => ['cowtown-farmers-market' => 'A-20', 'dallas-farmers-market' => 'B-11'],
                'products' => [
                    ['Sourdough loaf', 'baked-goods', 9.00, 'piece', 10, 'sourdough.webp', 'Two-day fermented sourdough.'],
                    ['Whole wheat bread', 'baked-goods', 6.00, 'piece', 15, 'wheat-bread.webp', 'Soft sandwich loaf, no preservatives.'],
                    ['Banana walnut cake', 'baked-goods', 12.00, 'piece', 6, 'banana-cake.webp', 'Moist loaf cake made with our neighbours\' bananas.'],
                ],
            ],
            [
                // El Paso ka approved farmer - wahan doosra (Wildflower Honey) jaan boojh ke pending hai
                'stall_name' => 'Mesilla Valley Growers',
                'contact_person' => 'Carlos Mendoza',
                'email' => 'mesillavalley@marketlink.test',
                'phone' => '(915) 555-0606',
                'bio' => 'Irrigated fields along the Rio Grande in Canutillo, growing tomatoes, pomegranates and green chile.',
                'address' => 'Canutillo, TX 79835',
                'latitude' => 31.9118,
                'longitude' => -106.5989,
                'cover_image' => 'images/farmers/mesilla-valley.webp',
                'status' => FarmerApprovalStatus::Approved,
                'joined_months_ago' => 2,
                'markets' => ['el-paso-farmers-market' => 'E-05'],
                'products' => [
                    ['Heirloom tomatoes', 'vegetables', 5.50, 'kg', 20, 'heirloom-tomatoes.webp', 'Mixed heirloom varieties, ripened on the plant.'],
                    ['Desert pomegranates', 'fruits', 5.00, 'kg', 14, 'desert-pomegranates.webp', 'Tart and sweet, picked in the cool early morning.'],
                    ['Roasted green chile salsa', 'honey-preserves', 7.50, 'piece', 12, 'green-chile-salsa.webp', '16 oz jar, made from our own Hatch-style chile.'],
                ],
            ],
            [
                // jaan boojh ke pending, taake demo mein admin approval dikha saken
                'stall_name' => 'Wildflower Honey Co.',
                'contact_person' => 'Usman Baloch',
                'email' => 'wildflower@marketlink.test',
                'phone' => '(915) 555-0505',
                'bio' => 'Raw honey from hives moved between mesquite and wildflower fields.',
                'address' => 'Anthony, TX 79821',
                'latitude' => 31.9993,
                'longitude' => -106.6053,
                'cover_image' => 'images/farmers/wildflower-honey.webp',
                'status' => FarmerApprovalStatus::Pending,
                // aaj sign up hua, is liye yahi ek stall "new" dikhta hai
                'joined_months_ago' => 0,
                'markets' => ['el-paso-farmers-market' => 'E-15'],
                'products' => [
                    ['Raw mesquite honey', 'honey-preserves', 14.00, 'piece', 8, 'mesquite-honey.webp', '1 lb jar of unfiltered mesquite honey.'],
                    ['Prickly pear jam', 'honey-preserves', 8.00, 'piece', 10, 'prickly-pear-jam.webp', 'Sweet and bright, made from wild prickly pear fruit.'],
                ],
            ],
        ];
    }
}
