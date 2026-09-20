<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Enums\NavigationLinkType;
use App\Enums\PageTemplate;
use App\Enums\ProductStatus;
use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Enums\ShippingMethodType;
use App\Enums\UserRole;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\BlogPost;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@yumyumhomemadepickles.com'],
            [
                'name' => 'YumYum Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
                'loyalty_points' => 0,
                'referral_code' => 'YUMADMIN',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'customer@yumyumhomemadepickles.com'],
            [
                'name' => 'Jane Customer',
                'password' => Hash::make('password'),
                'role' => UserRole::Customer,
                'email_verified_at' => now(),
                'loyalty_points' => 120,
                'referral_code' => 'YUMFAM',
            ],
        );

        app(SettingsService::class)->setMany([
            'store.name' => 'YumYum Pickles',
            'store.email' => 'hello@yumyumhomemadepickles.com',
            'store.phone' => '+91 99999 99999',
            'store.tagline' => 'The Taste of Tradition',
            'store.whatsapp' => '919999999999',
            'store.youtube' => 'https://youtube.com/@yumyumpickles',
            'store.currency' => 'INR',
            'seo.default_title' => 'YumYum Pickles — The Taste of Tradition',
            'seo.default_description' => 'Homemade Goan and coastal pickles, packed in glass and delivered across India.',
            'social.facebook' => 'https://facebook.com/yumyumpickles',
            'social.instagram' => 'https://instagram.com/yumyumpickles',
            'social.youtube' => 'https://youtube.com/@yumyumpickles',
            'checkout.guest_checkout_enabled' => true,
            'checkout.tax_rate_basis_points' => 0,
        ], 'store');

        $this->content();
        $this->pages();
        $this->shipping();
        $this->catalog();
        $this->recipes($admin);
        $this->coupons();
        $this->navigation();
        $this->banners();
    }

    private function content(): void
    {
        $settings = app(SettingsService::class);

        $settings->setMany([
            'storefront.announcements' => [
                'Free Shipping Above ₹499',
                'Freshly Prepared & Hygienically Packed',
                'COD Available',
                'Pan India Delivery',
            ],
            'storefront.story_steps' => [
                ['title' => 'Ingredient Selection', 'copy' => 'We pick firm raw mangoes, bright lemons and whole spices by hand — never from a mixed industrial lot.', 'image' => 'yumyum-spices.jpg'],
                ['title' => 'Traditional Preparation', 'copy' => 'Fruit is washed, sun-dried and cut the way our family has done it for decades. No shortcuts, no machines where a knife belongs.', 'image' => 'yumyum-story.jpg'],
                ['title' => 'Spice Mixing', 'copy' => 'Mustard, chilli, fenugreek and turmeric are roasted and ground in small batches so every jar smells like a real kitchen.', 'image' => 'yumyum-story.jpg'],
                ['title' => 'Sun Curing', 'copy' => 'The pickle rests in sunlight and time. That is how the oil turns gold and the flavour becomes round, not raw.', 'image' => 'yumyum-hero.jpg'],
                ['title' => 'Hygienic Packaging', 'copy' => 'Each glass jar is sealed after a fresh batch, labelled by hand and packed to travel safely across India.', 'image' => 'yumyum-four.png'],
                ['title' => 'Fresh Delivery', 'copy' => 'From our kitchen to your table — with care, tracking and a spoonful of home waiting at the door.', 'image' => 'yumyum-lifestyle.jpg'],
            ],
            'storefront.why_choose' => [
                ['title' => 'Traditional Family Recipes', 'copy' => 'The same masala ratios our grandmother taught us, written down so nothing gets lost.'],
                ['title' => 'Homemade Goodness', 'copy' => 'Small pots, not factory vats. You can taste the difference in the oil and the crunch.'],
                ['title' => 'Fresh Ingredients', 'copy' => 'Seasonal fruit and whole spices, chosen for aroma first and appearance second.'],
                ['title' => 'No Artificial Preservatives', 'copy' => 'Salt, oil, sunshine and patience do the keeping. No added colours or flavour chemicals.'],
                ['title' => 'Hygienic Preparation', 'copy' => 'Clean dry spoons, sealed glass jars and a kitchen that treats food the way family does.'],
                ['title' => 'Fast Delivery', 'copy' => 'Pan India shipping, COD and free delivery above ₹499 — so the jar arrives while it still feels special.'],
            ],
            'storefront.videos' => [
                ['title' => 'Prawns Balchao batch', 'tag' => 'Behind The Scenes', 'views' => '24K', 'image' => 'prawns-balchao.png'],
                ['title' => 'Meet the bombil jar', 'tag' => 'YouTube Shorts', 'views' => '18K', 'image' => 'bombil-pickle.png'],
                ['title' => 'Achaar paratha', 'tag' => 'Recipe Videos', 'views' => '41K', 'image' => 'yumyum-recipes.jpg'],
                ['title' => 'First taste', 'tag' => 'Customer Reactions', 'views' => '33K', 'image' => 'yumyum-lifestyle.jpg'],
                ['title' => 'The four-jar set', 'tag' => 'Instagram Reels', 'views' => '21K', 'image' => 'yumyum-four.png'],
                ['title' => 'Brinjal pickle day', 'tag' => 'Behind The Scenes', 'views' => '29K', 'image' => 'brinjal-pickle.png'],
            ],
            'storefront.testimonials' => [
                ['name' => 'Meera Shah', 'city' => 'Mumbai', 'rating' => 5, 'text' => 'The prawns balchao tastes like a Goan home kitchen. The jar disappeared in four days!', 'initials' => 'MS'],
                ['name' => 'Ananya Rao', 'city' => 'Bengaluru', 'rating' => 5, 'text' => 'Brinjal pickle is chunky, clean and perfectly spiced. Delivery to Bengaluru was quick.', 'initials' => 'AR'],
                ['name' => 'Rohit Malhotra', 'city' => 'Dubai', 'rating' => 5, 'text' => 'I carried the four-jar set back to Dubai. Finally, genuine homemade flavour without the fuss.', 'initials' => 'RM'],
                ['name' => 'Kavya Iyer', 'city' => 'Chennai', 'rating' => 5, 'text' => 'The tendli pickle made my rice plates feel like Sunday at amma\'s house.', 'initials' => 'KI'],
                ['name' => 'Imran Qureshi', 'city' => 'Hyderabad', 'rating' => 4, 'text' => 'Bombil is honest, oil is clean, and dangerously moreish with hot rice.', 'initials' => 'IQ'],
                ['name' => 'Sana Kapoor', 'city' => 'Delhi', 'rating' => 5, 'text' => 'Gifted the tradition set to my in-laws. They asked for the brand before the evening was over.', 'initials' => 'SK'],
            ],
            'storefront.instagram' => [
                ['image' => 'prawns-balchao.png', 'likes' => 1284, 'reel' => true, 'alt' => 'Prawns Balchao jar on the coast'],
                ['image' => 'yumyum-lifestyle.jpg', 'likes' => 986, 'reel' => false, 'alt' => 'Family table with pickle'],
                ['image' => 'yumyum-four.png', 'likes' => 1540, 'reel' => false, 'alt' => 'Four YumYum pickle jars'],
                ['image' => 'brinjal-pickle.png', 'likes' => 2104, 'reel' => true, 'alt' => 'Brinjal pickle reel'],
                ['image' => 'tendli-pickle.png', 'likes' => 876, 'reel' => false, 'alt' => 'Tendli pickle with fresh ivy gourd'],
                ['image' => 'bombil-pickle.png', 'likes' => 1198, 'reel' => true, 'alt' => 'Bombil pickle with dried Bombay duck'],
                ['image' => 'yumyum-recipes.jpg', 'likes' => 1672, 'reel' => false, 'alt' => 'Meals with homemade pickle'],
                ['image' => 'yumyum-story.jpg', 'likes' => 743, 'reel' => false, 'alt' => 'Spice mixing in the kitchen'],
            ],
            'storefront.faqs' => [
                ['q' => 'Do you use artificial preservatives?', 'a' => 'No. Our pickles are preserved the traditional way — with salt, cold-pressed oil, spices and sun curing.'],
                ['q' => 'How long does a jar last?', 'a' => 'Best before 12 months from packing if stored in a cool, dry place. Always use a clean, dry spoon.'],
                ['q' => 'Do you ship across India?', 'a' => 'Yes. We deliver pan India. Orders above ₹499 ship free. COD is available on most pincodes.'],
                ['q' => 'Can I order as a gift?', 'a' => 'Absolutely. Add a note at checkout and we will pack it as a gift-ready set.'],
                ['q' => 'What oil do you use?', 'a' => 'Vegetable jars use cold-pressed mustard oil. Coastal specials such as prawns balchao and bombil follow their traditional coastal masala.'],
                ['q' => 'Is the pickle very spicy?', 'a' => 'Each product is labelled Mild, Medium or Hot. Start with tendli if you prefer gentler heat.'],
                ['q' => 'Do you ship internationally?', 'a' => 'We currently deliver across India. NRI families often carry jars or order to Indian addresses. Write to us for bulk gifting.'],
                ['q' => 'How do I return a jar?', 'a' => 'If a jar arrives damaged or leaking, write to us within 48 hours with photos. We replace or refund promptly.'],
            ],
            'storefront.social_proof' => [
                ['name' => 'Meera', 'city' => 'Mumbai', 'product' => 'Prawns Balchao'],
                ['name' => 'Arjun', 'city' => 'Pune', 'product' => 'Brinjal Pickle'],
                ['name' => 'Kavya', 'city' => 'Chennai', 'product' => 'Tendli Pickle'],
                ['name' => 'Rohit', 'city' => 'Dubai', 'product' => 'The Taste of Tradition Set'],
                ['name' => 'Sana', 'city' => 'Delhi', 'product' => 'Bombil Pickle'],
                ['name' => 'Imran', 'city' => 'Hyderabad', 'product' => 'Prawns Balchao'],
            ],
            'storefront.policies' => [
                'shipping' => [
                    'title' => 'Shipping Policy',
                    'intro' => 'We pack every jar as if it were travelling to a relative — tightly, honestly and with a little extra care.',
                    'sections' => [
                        ['title' => 'Delivery timelines', 'body' => 'Most metros receive orders in 3–5 working days. The rest of India typically takes 5–8 working days. Remote pincodes may take a little longer.'],
                        ['title' => 'Free shipping', 'body' => 'Enjoy free shipping on all prepaid and COD orders above ₹499. Below that, a flat packing & courier fee of ₹49 applies.'],
                        ['title' => 'COD', 'body' => 'Cash on delivery is available on most serviceable pincodes. Please keep the exact amount ready for the courier partner.'],
                        ['title' => 'Packaging', 'body' => 'Glass jars are sealed, bubble-wrapped and boxed. If a jar arrives damaged, we replace it — no questions, only photographs.'],
                    ],
                ],
                'refunds' => [
                    'title' => 'Refund Policy',
                    'intro' => 'Food is personal. If something is not right with your jar, we would rather make it right than argue the small print.',
                    'sections' => [
                        ['title' => 'Damaged or leaking jars', 'body' => 'Write to hello@yumyumhomemadepickles.com within 48 hours of delivery with unboxing photos. We will ship a replacement or refund the item.'],
                        ['title' => 'Wrong item', 'body' => 'If we send the wrong flavour, we will collect or ask you to keep it and send the correct jar at our cost.'],
                        ['title' => 'Change of mind', 'body' => 'Because this is fresh food, we cannot accept returns once a jar has been opened. Unopened jars may be discussed case by case.'],
                        ['title' => 'Refund timelines', 'body' => 'Approved refunds are processed to the original payment method within 5–7 working days. COD refunds are issued by UPI or bank transfer.'],
                    ],
                ],
                'privacy' => [
                    'title' => 'Privacy Policy',
                    'intro' => 'We collect only what we need to cook, pack and deliver your order — and we do not sell your data.',
                    'sections' => [
                        ['title' => 'What we collect', 'body' => 'Name, phone, email, delivery address, order history and, if you choose, wishlist and review activity. Payments are processed by our payment partner; we do not store card numbers.'],
                        ['title' => 'How we use it', 'body' => 'To fulfil orders, share delivery updates, remember your cart, and — only if you opt in — send new flavour notes and offers.'],
                        ['title' => 'Your choices', 'body' => 'You can update your profile, addresses and marketing preferences from your account at any time.'],
                        ['title' => 'Contact', 'body' => 'Questions about your data? Write to hello@yumyumhomemadepickles.com.'],
                    ],
                ],
                'terms' => [
                    'title' => 'Terms & Conditions',
                    'intro' => 'By shopping at YumYum Pickles you agree to a simple, fair set of house rules.',
                    'sections' => [
                        ['title' => 'The jars', 'body' => 'Photographs are of real batches, but homemade food varies slightly from pot to pot. That is a feature, not a flaw.'],
                        ['title' => 'Allergens', 'body' => 'Our kitchen handles mustard, sesame and nuts. Please read each product page if you have allergies.'],
                        ['title' => 'Pricing', 'body' => 'Prices include taxes. Offers, loyalty points and referral rewards cannot always be stacked. We will show the best applicable total at checkout.'],
                        ['title' => 'Use of the site', 'body' => 'Please do not misuse the website, scrape content, or place fraudulent orders. We may cancel orders that look unsafe or incomplete.'],
                    ],
                ],
            ],
        ], 'storefront');
    }

    private function pages(): void
    {
        $pages = [
            ['Our Story', 'our-story', 'How YumYum pickles are made — from spice mixing to sun curing.', PageTemplate::Default],
            ['Contact', 'contact', 'Reach the YumYum kitchen by email, phone or WhatsApp.', PageTemplate::Contact],
            ['FAQ', 'faq', 'Answers about spice, shipping, storage and gifts.'],
            ['Privacy Policy', 'privacy', 'How we collect and use your information.'],
            ['Refund Policy', 'refunds', 'When and how we issue refunds.'],
            ['Shipping Policy', 'shipping', 'Delivery times, packing and COD.'],
            ['Terms & Conditions', 'terms', 'House rules for shopping at YumYum Pickles.'],
        ];

        foreach ($pages as $page) {
            [$title, $slug, $excerpt] = $page;
            $template = $page[3] ?? PageTemplate::Default;

            Page::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => '<p>'.$excerpt.'</p>',
                    'status' => PublishStatus::Published,
                    'template' => $template,
                    'published_at' => now()->subDay(),
                ],
            );
        }
    }

    private function shipping(): void
    {
        ShippingMethod::query()->updateOrCreate(
            ['name' => 'Standard shipping'],
            [
                'description' => '3–8 working days across India',
                'type' => ShippingMethodType::FreeOverThreshold,
                'rate_amount' => 4900,
                'free_over_amount' => 49900,
                'is_active' => true,
                'position' => 1,
            ],
        );
    }

    private function catalog(): void
    {
        $collections = [];

        foreach ([
            ['Prawn', 'prawn', 'Bold. Spicy. Homemade.', 'prawns-balchao.png'],
            ['Brinjal', 'brinjal', 'Chunky & homemade', 'brinjal-pickle.png'],
            ['Bombil', 'bombil', 'Dried Bombay duck', 'bombil-pickle.png'],
            ['Tendli', 'tendli', 'Tender everyday pickle', 'tendli-pickle.png'],
            ['Gift Set', 'gift-set', 'Four jars, one homemade love', 'yumyum-four.png'],
        ] as $index => [$title, $slug, $description, $image]) {
            $collections[$slug] = Collection::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'description' => $description,
                    'status' => PublishStatus::Published,
                    'published_at' => now()->subDay(),
                    'position' => $index + 1,
                    'image_disk' => 'public',
                    'image_path' => $this->copyAsset($image, 'collections'),
                ],
            );
        }

        $products = [
            [
                'slug' => 'prawns-balchao',
                'title' => 'Prawns Balchao',
                'collection' => 'prawn',
                'price' => 34900,
                'compare' => 39900,
                'spice' => 'Hot',
                'badge' => 'Bestseller',
                'is_new' => false,
                'description' => 'Bold, spicy Goan-style prawn pickle — homemade with fresh prawns, red chilli and a coastal masala that lingers.',
                'story' => 'Balchao is a taste of the coast. We cook fresh prawns slowly with chilli, vinegar and whole spices until the oil turns brick-red and the flavour turns deep, not just hot.',
                'ingredients' => ['Prawns', 'Red chilli', 'Vinegar', 'Garlic', 'Ginger', 'Mustard seeds', 'Turmeric', 'Rock salt'],
                'nutrition' => [
                    ['label' => 'Energy', 'value' => '58 kcal'],
                    ['label' => 'Fat', 'value' => '3.8 g'],
                    ['label' => 'Carbs', 'value' => '1.6 g'],
                    ['label' => 'Protein', 'value' => '4.8 g'],
                    ['label' => 'Trans fat', 'value' => '0 g'],
                    ['label' => 'Added colour', 'value' => 'None'],
                ],
                'pairs' => ['bombil-pickle', 'brinjal-pickle', 'yumyum-four'],
                'images' => [
                    ['file' => 'prawns-balchao.png', 'alt' => 'YumYum Prawns Balchao jar with prawns'],
                    ['file' => 'prawns-balchao-story.png', 'alt' => 'Prawns Balchao jar held in the kitchen'],
                    ['file' => 'yumyum-four.png', 'alt' => 'Prawns Balchao in the YumYum four-jar set'],
                ],
                'reviews' => [
                    ['Meera Shah', 'Mumbai', 5, '12 Aug 2026', 'This is the prawn pickle I have been looking for since I left Goa. Heat is rounded and the prawns stay plump.'],
                    ['Dev Patel', 'Ahmedabad', 5, '3 Aug 2026', 'Ordered 800g and we are already planning the next jar. Incredible with rice and papad.'],
                ],
            ],
            [
                'slug' => 'brinjal-pickle',
                'title' => 'Brinjal Pickle',
                'collection' => 'brinjal',
                'price' => 24900,
                'compare' => 29900,
                'spice' => 'Medium',
                'badge' => 'Family favourite',
                'is_new' => false,
                'description' => 'Chunky brinjal, traditional spices and homemade goodness — a family jar that belongs next to dal and rice.',
                'story' => 'We cut firm brinjals by hand so every piece stays meaty after curing. The masala is warm, not harsh — the kind of pickle people finish standing at the fridge.',
                'ingredients' => ['Brinjal', 'Mustard oil', 'Mustard seeds', 'Red chilli', 'Fenugreek', 'Turmeric', 'Asafoetida', 'Rock salt'],
                'nutrition' => [
                    ['label' => 'Energy', 'value' => '40 kcal'],
                    ['label' => 'Fat', 'value' => '3.2 g'],
                    ['label' => 'Carbs', 'value' => '2.4 g'],
                    ['label' => 'Protein', 'value' => '0.6 g'],
                    ['label' => 'Trans fat', 'value' => '0 g'],
                    ['label' => 'Added colour', 'value' => 'None'],
                ],
                'pairs' => ['tendli-pickle', 'prawns-balchao', 'yumyum-four'],
                'images' => [
                    ['file' => 'brinjal-pickle.png', 'alt' => 'YumYum Brinjal Pickle jar with fresh brinjals'],
                    ['file' => 'yumyum-four.png', 'alt' => 'Brinjal pickle in the YumYum four-jar set'],
                ],
                'reviews' => [
                    ['Ananya Rao', 'Bengaluru', 5, '9 Aug 2026', 'Chunky brinjal, clean oil, perfect with stuffed parathas on Sunday.'],
                ],
            ],
            [
                'slug' => 'bombil-pickle',
                'title' => 'Bombil Pickle',
                'collection' => 'bombil',
                'price' => 32900,
                'compare' => 37900,
                'spice' => 'Medium',
                'badge' => 'Coastal special',
                'is_new' => false,
                'description' => 'Dried Bombay duck (bombil) pickled with traditional spices — authentic, wholesome and deeply coastal.',
                'story' => 'We choose carefully dried bombil, then rest it in a homemade masala until the fish turns tender and the oil smells of the Konkan kitchen.',
                'ingredients' => ['Dried bombil', 'Red chilli', 'Garlic', 'Ginger', 'Mustard seeds', 'Coriander', 'Turmeric', 'Rock salt'],
                'nutrition' => [
                    ['label' => 'Energy', 'value' => '52 kcal'],
                    ['label' => 'Fat', 'value' => '3.4 g'],
                    ['label' => 'Carbs', 'value' => '1.4 g'],
                    ['label' => 'Protein', 'value' => '4.2 g'],
                    ['label' => 'Trans fat', 'value' => '0 g'],
                    ['label' => 'Added colour', 'value' => 'None'],
                ],
                'pairs' => ['prawns-balchao', 'tendli-pickle', 'yumyum-four'],
                'images' => [
                    ['file' => 'bombil-pickle.png', 'alt' => 'YumYum Bombil Pickle with dried Bombay duck'],
                    ['file' => 'bombil-story.png', 'alt' => 'Bombil pickle jar in the YumYum kitchen'],
                    ['file' => 'yumyum-four.png', 'alt' => 'Bombil pickle in the YumYum four-jar set'],
                ],
                'reviews' => [
                    ['Imran Qureshi', 'Hyderabad', 5, '30 Jul 2026', 'Proper coastal flavour. The bombil is tender, not dry. Keep a plate of rice nearby.'],
                ],
            ],
            [
                'slug' => 'tendli-pickle',
                'title' => 'Tendli Pickle',
                'collection' => 'tendli',
                'price' => 23900,
                'compare' => 27900,
                'spice' => 'Mild',
                'badge' => 'New',
                'is_new' => true,
                'description' => 'Tender ivy gourd in a gentle homemade pickle — traditional spices, a clean crunch, and everyday comfort.',
                'story' => 'Tendli is picked young so it stays tender. A lighter masala keeps the vegetable bright — perfect for people who want homemade flavour without a fiery finish.',
                'ingredients' => ['Tendli', 'Mustard oil', 'Mustard seeds', 'Coriander', 'Turmeric', 'Green chilli', 'Ginger', 'Rock salt'],
                'nutrition' => [
                    ['label' => 'Energy', 'value' => '36 kcal'],
                    ['label' => 'Fat', 'value' => '2.8 g'],
                    ['label' => 'Carbs', 'value' => '2.2 g'],
                    ['label' => 'Protein', 'value' => '0.5 g'],
                    ['label' => 'Trans fat', 'value' => '0 g'],
                    ['label' => 'Added colour', 'value' => 'None'],
                ],
                'pairs' => ['brinjal-pickle', 'bombil-pickle', 'yumyum-four'],
                'images' => [
                    ['file' => 'tendli-pickle.png', 'alt' => 'YumYum Tendli Pickle jar with fresh tendli'],
                    ['file' => 'yumyum-four.png', 'alt' => 'Tendli pickle in the YumYum four-jar set'],
                ],
                'reviews' => [
                    ['Kavya Iyer', 'Chennai', 5, '18 Jul 2026', 'Gentle enough for everyday. The tendli still has a little bite.'],
                ],
            ],
            [
                'slug' => 'yumyum-four',
                'title' => 'The Taste of Tradition Set',
                'collection' => 'gift-set',
                'price' => 99900,
                'compare' => 129900,
                'spice' => 'Medium',
                'badge' => 'Gift set',
                'is_new' => false,
                'gift' => true,
                'description' => 'Four pickles. One homemade love. Prawns Balchao, Brinjal, Bombil and Tendli — packed together for gifting and first tastings.',
                'story' => 'This is the set we send to family. One coastal heat, one chunky vegetable, one dried-fish classic, and one gentle everyday jar.',
                'ingredients' => ['Prawns Balchao', 'Brinjal Pickle', 'Bombil Pickle', 'Tendli Pickle'],
                'nutrition' => [
                    ['label' => 'Jars', 'value' => '4'],
                    ['label' => 'Each', 'value' => '250g'],
                    ['label' => 'Preservatives', 'value' => 'None'],
                    ['label' => 'FSSAI', 'value' => 'Licensed'],
                    ['label' => 'Trans fat', 'value' => '0 g'],
                    ['label' => 'Added colour', 'value' => 'None'],
                ],
                'pairs' => ['prawns-balchao', 'brinjal-pickle', 'bombil-pickle'],
                'images' => [
                    ['file' => 'yumyum-four.png', 'alt' => 'YumYum four homemade pickle jars'],
                    ['file' => 'prawns-balchao.png', 'alt' => 'Prawns Balchao from the set'],
                    ['file' => 'brinjal-pickle.png', 'alt' => 'Brinjal pickle from the set'],
                    ['file' => 'tendli-pickle.png', 'alt' => 'Tendli pickle from the set'],
                ],
                'reviews' => [
                    ['Sana Kapoor', 'Delhi', 5, '2 Aug 2026', 'The gift set is how I introduce YumYum. Four distinct jars, one honest kitchen.'],
                    ['Rohit Malhotra', 'Dubai', 5, '11 Aug 2026', 'Packed this for my flight back. Still the most requested box in our kitchen.'],
                ],
            ],
        ];

        foreach ($products as $index => $payload) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'title' => $payload['title'],
                    'description' => $payload['description'],
                    'body_html' => '<p>'.$payload['story'].'</p>',
                    'status' => ProductStatus::Active,
                    'vendor' => 'YumYum Pickles',
                    'product_type' => $collections[$payload['collection']]->title,
                    'published_at' => now()->subDay(),
                    'position' => $index + 1,
                    'metadata' => [
                        'spice' => $payload['spice'],
                        'story' => $payload['story'],
                        'ingredients' => $payload['ingredients'],
                        'nutrition' => $payload['nutrition'],
                        'pairs_with' => $payload['pairs'],
                        'badge' => $payload['badge'],
                        'is_new' => $payload['is_new'],
                    ],
                ],
            );

            $isGift = $payload['gift'] ?? false;
            $weights = $isGift
                ? [['4 × 250g', $payload['price'], $payload['compare']]]
                : [
                    ['250g', (int) round($payload['price'] * 0.7), (int) round($payload['compare'] * 0.7)],
                    ['400g', $payload['price'], $payload['compare']],
                    ['800g', (int) round($payload['price'] * 1.8), (int) round($payload['compare'] * 1.8)],
                ];

            ProductOption::query()->updateOrCreate(
                ['product_id' => $product->id, 'name' => 'Weight'],
                ['position' => 1, 'values' => array_column($weights, 0)],
            );

            foreach ($weights as $position => [$label, $price, $compare]) {
                ProductVariant::query()->updateOrCreate(
                    ['sku' => strtoupper($payload['slug']).'-'.preg_replace('/[^A-Z0-9]+/', '', strtoupper($label))],
                    [
                        'product_id' => $product->id,
                        'title' => $label,
                        'price_amount' => $price,
                        'compare_at_price_amount' => $compare,
                        'option1' => $label,
                        'inventory_quantity' => 80,
                        'track_inventory' => true,
                        'position' => $position + 1,
                        'weight' => $isGift ? 1 : ((int) filter_var($label, FILTER_SANITIZE_NUMBER_INT)) / 1000,
                        'weight_unit' => 'kg',
                    ],
                );
            }

            $product->images()->delete();

            foreach ($payload['images'] as $imageIndex => $image) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'disk' => 'public',
                    'path' => $this->copyAsset($image['file'], 'products'),
                    'alt' => $image['alt'],
                    'position' => $imageIndex + 1,
                ]);
            }

            $product->collections()->syncWithoutDetaching([
                $collections[$payload['collection']]->id => ['position' => 1],
            ]);

            foreach ($payload['reviews'] as $review) {
                [$name, $city, $rating, $date, $body] = $review;

                ProductReview::query()->updateOrCreate(
                    ['product_id' => $product->id, 'author_name' => $name, 'body' => $body],
                    [
                        'author_city' => $city,
                        'rating' => $rating,
                        'status' => ReviewStatus::Approved,
                        'reviewed_at' => \Illuminate\Support\Carbon::parse($date),
                    ],
                );
            }
        }
    }

    private function recipes(User $author): void
    {
        $blog = Blog::query()->updateOrCreate(
            ['slug' => 'recipes'],
            [
                'title' => 'Recipes',
                'description' => 'Quick meals built around a YumYum jar.',
            ],
        );

        $recipes = [
            ['pickle-paratha', 'Pickle Paratha', '15 min', 'Easy', 'Brinjal Pickle', 'Leftover dough, a spoon of brinjal pickle, and a hot tawa — breakfast sorted.', ['Roll leftover wheat dough into a small disc.', 'Spread a thin layer of YumYum brinjal pickle and fold like an envelope.', 'Roll again gently and cook on a hot tawa with ghee until golden.', 'Serve with cold curd and extra pickle on the side.']],
            ['pickle-rice', 'Pickle Rice', '10 min', 'Easy', 'Prawns Balchao', 'Warm rice, a spoon of prawns balchao and a drizzle of pickle oil. Lunch in minutes.', ['Fluff hot steamed rice in a bowl.', 'Add a generous spoon of prawns balchao and a little of its oil.', 'Mix quickly so every grain is coated.', 'Finish with a fried papad or a fried egg.']],
            ['pickle-sandwich', 'Pickle Sandwich', '12 min', 'Easy', 'Tendli Pickle', 'Toasted bread, butter, cheese and a gentle tendli pickle crunch.', ['Butter two slices of bread and toast one side.', 'Layer cheese, thinly sliced onion and tendli pickle.', 'Toast until the cheese melts and the edges crisp.', 'Cut into triangles and serve immediately.']],
            ['pickle-wrap', 'Pickle Wrap', '20 min', 'Easy', 'Bombil Pickle', 'A working-lunch wrap with yoghurt, herbs and a coastal bombil kick.', ['Warm a roti or tortilla.', 'Spread hung curd mixed with herbs.', 'Add grilled vegetables or leftover chicken and bombil pickle.', 'Roll tight and pack for the office.']],
            ['pickle-dosa', 'Pickle Dosa', '25 min', 'Medium', 'Tendli Pickle', 'Crisp dosa, coconut chutney and a spoon of tendli pickle on the side.', ['Spread dosa batter thin on a hot tawa.', 'Cook until the edges lift and the centre is lacey.', 'Serve with coconut chutney and YumYum tendli pickle.', 'Spoon a little pickle oil over the dosa for extra perfume.']],
        ];

        foreach ($recipes as $recipe) {
            [$slug, $title, $time, $difficulty, $pickle, $excerpt, $steps] = $recipe;

            BlogPost::query()->updateOrCreate(
                ['blog_id' => $blog->id, 'slug' => $slug],
                [
                    'user_id' => $author->id,
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => '<ol>'.implode('', array_map(fn (string $step): string => '<li>'.$step.'</li>', $steps)).'</ol>',
                    'status' => PublishStatus::Published,
                    'published_at' => now()->subDay(),
                    'featured_image_disk' => 'public',
                    'featured_image_path' => $this->copyAsset('yumyum-recipes.jpg', 'recipes'),
                    'metadata' => [
                        'time' => $time,
                        'difficulty' => $difficulty,
                        'pickle' => $pickle,
                        'steps' => $steps,
                    ],
                ],
            );
        }
    }

    private function coupons(): void
    {
        $coupons = [
            ['YUMYUM10', '10% off your order', CouponType::Percentage, 1000],
            ['FIRST50', '₹50 off for first jars', CouponType::FixedAmount, 5000],
            ['YUMFAM', 'Referral reward — ₹100 off', CouponType::FixedAmount, 10000],
            ['FREESHIP', 'Free shipping unlocked', CouponType::FreeShipping, 0],
            ['POINTS50', '100 loyalty points redeemed', CouponType::FixedAmount, 5000],
        ];

        foreach ($coupons as [$code, $description, $type, $value]) {
            Coupon::query()->updateOrCreate(
                ['code' => $code],
                [
                    'description' => $description,
                    'type' => $type,
                    'value' => $value,
                    'is_active' => true,
                ],
            );
        }
    }

    private function navigation(): void
    {
        NavigationItem::query()->where('menu', NavigationItem::MenuHeader)->delete();

        $items = [
            ['Shop', NavigationLinkType::Catalog, null, '/shop'],
            ['Our Story', NavigationLinkType::Page, Page::query()->where('slug', 'our-story')->value('id'), null],
            ['Recipes', NavigationLinkType::Blog, Blog::query()->where('slug', 'recipes')->value('id'), null],
            ['Contact', NavigationLinkType::Page, Page::query()->where('slug', 'contact')->value('id'), null],
        ];

        foreach ($items as $index => [$title, $type, $resourceId, $url]) {
            NavigationItem::query()->create([
                'menu' => NavigationItem::MenuHeader,
                'title' => $title,
                'type' => $type,
                'resource_id' => $resourceId,
                'url' => $url,
                'position' => $index + 1,
            ]);
        }
    }

    private function banners(): void
    {
        $slides = [
            ['The Taste of Tradition', 'Homemade coastal pickles, packed in glass.', 'Shop the jars', '/shop', 'yumyum-hero.jpg'],
            ['Prawns Balchao', 'Bold Goan heat, plump prawns, brick-red oil.', 'Taste Balchao', '/product/prawns-balchao', 'prawns-balchao.png'],
            ['Four jars. One kitchen.', 'The gift set families ask for by name.', 'Gift the set', '/product/yumyum-four', 'yumyum-four.png'],
        ];

        foreach ($slides as $index => [$title, $subtitle, $label, $url, $image]) {
            Banner::query()->updateOrCreate(
                ['title' => $title],
                [
                    'subtitle' => $subtitle,
                    'button_label' => $label,
                    'button_url' => $url,
                    'image_disk' => 'public',
                    'image_path' => $this->copyAsset($image, 'banners'),
                    'alt' => $title,
                    'position' => $index + 1,
                    'status' => PublishStatus::Published,
                    'published_at' => now()->subDay(),
                ],
            );
        }
    }

    private function copyAsset(string $filename, string $folder): string
    {
        $destRel = $folder.'/'.$filename;
        $destAbs = storage_path('app/public/'.$destRel);
        File::ensureDirectoryExists(dirname($destAbs));

        $candidates = [
            base_path('resources/js/assets/products/'.$filename),
            base_path('resources/js/assets/'.$filename),
            base_path('../yumyum-pickle-experience/src/assets/products/'.$filename),
            base_path('../yumyum-pickle-experience/src/assets/'.$filename),
            storage_path('app/public/'.$destRel),
        ];

        foreach ($candidates as $source) {
            if (is_file($source) && realpath($source) !== realpath($destAbs)) {
                File::copy($source, $destAbs);

                return $destRel;
            }
        }

        return $destRel;
    }
}
