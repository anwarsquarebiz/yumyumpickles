<?php

use App\Models\Banner;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;

it('renders the home page', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/home')
            ->has('banners.data')
            ->has('collections.data')
            ->has('featuredProducts.data')
        );
});

it('shows published collections and featured products on the home page', function () {
    $collection = Collection::factory()->create(['title' => 'Erectile Dysfunction', 'position' => 1]);
    Collection::factory()->draft()->create(['title' => 'Hidden collection']);

    foreach (['cenforce-100-mg', 'cenforce-200-mg', 'vidalista-20-mg', 'vidalista-60-mg'] as $slug) {
        ProductVariant::factory()->for(Product::factory()->create([
            'title' => $slug,
            'slug' => $slug,
        ]))->create();
    }

    ProductVariant::factory()->for(Product::factory()->create(['title' => 'Should not appear']))->create();
    ProductVariant::factory()->for(Product::factory()->draft()->create(['title' => 'Hidden product']))->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/home')
            ->has('collections.data', 1)
            ->where('collections.data.0.title', $collection->title)
            ->has('featuredProducts.data', 4)
            ->where('featuredProducts.data.0.slug', 'cenforce-100-mg')
            ->where('featuredProducts.data.1.slug', 'cenforce-200-mg')
            ->where('featuredProducts.data.2.slug', 'vidalista-20-mg')
            ->where('featuredProducts.data.3.slug', 'vidalista-60-mg')
        );
});

it('omits unpublished featured slugs from the home page', function () {
    ProductVariant::factory()->for(Product::factory()->create(['slug' => 'cenforce-100-mg']))->create();
    ProductVariant::factory()->for(Product::factory()->draft()->create(['slug' => 'cenforce-200-mg']))->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('featuredProducts.data', 1)
            ->where('featuredProducts.data.0.slug', 'cenforce-100-mg')
        );
});

it('shows live banners on the home page and hides drafts', function () {
    Banner::factory()->create(['title' => 'Visible slide', 'position' => 1]);
    Banner::factory()->draft()->create(['title' => 'Hidden slide']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/home')
            ->has('banners.data', 1)
            ->where('banners.data.0.title', 'Visible slide')
        );
});

it('hides banners that are outside their schedule', function () {
    Banner::factory()->create([
        'title' => 'Future promo',
        'starts_at' => now()->addDay(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('banners.data', 0));
});

it('lists published products', function () {
    $product = Product::factory()->create(['title' => 'Visible Product']);
    ProductVariant::factory()->for($product)->create();

    $this->get('/products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/products/index')
            ->has('products.data', 1)
            ->has('products.meta.links')
            ->where('products.meta.total', 1)
            ->where('products.data.0.title', 'Visible Product')
        );
});

it('hides drafts, archived and scheduled products from the listing', function () {
    ProductVariant::factory()->for(Product::factory()->draft())->create();
    ProductVariant::factory()->for(Product::factory()->archived())->create();
    ProductVariant::factory()->for(Product::factory()->scheduled())->create();

    $this->get('/products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products.data', 0));
});

it('shows a published product', function () {
    $product = Product::factory()->create(['slug' => 'vitamin-c']);
    ProductVariant::factory()->for($product)->priced(1250)->create();

    $this->get('/products/vitamin-c')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/products/show')
            ->where('product.data.slug', 'vitamin-c')
            ->where('product.data.variants.0.price.formatted', '$12.50')
        );
});

it('includes option values so the product page can show variant pickers', function () {
    $product = Product::factory()->create(['slug' => 'ibuprofen']);
    ProductOption::factory()->named('Strength', 1, ['200mg', '400mg'])->for($product)->create();

    ProductVariant::factory()->for($product)->priced(500)->create([
        'title' => '200mg',
        'option1' => '200mg',
        'position' => 1,
    ]);
    ProductVariant::factory()->for($product)->priced(800)->create([
        'title' => '400mg',
        'option1' => '400mg',
        'position' => 2,
    ]);

    $this->get('/products/ibuprofen')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/products/show')
            ->has('product.data.options', 1)
            ->where('product.data.options.0.name', 'Strength')
            ->where('product.data.options.0.values', ['200mg', '400mg'])
            ->has('product.data.variants', 2)
            ->where('product.data.variants.0.option1', '200mg')
            ->where('product.data.variants.1.option1', '400mg')
            ->where('product.data.variants.0.display_title', '200mg')
            ->where('product.data.variants.1.display_title', '400mg')
        );
});

it('includes every variant when a product has no options', function () {
    $product = Product::factory()->create(['slug' => 'omega-3']);

    ProductVariant::factory()->for($product)->priced(1299)->create([
        'title' => '60 capsules',
        'position' => 1,
    ]);
    ProductVariant::factory()->for($product)->priced(2199)->create([
        'title' => '120 capsules',
        'position' => 2,
    ]);

    $this->get('/products/omega-3')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/products/show')
            ->has('product.data.options', 0)
            ->has('product.data.variants', 2)
            ->where('product.data.variants.0.display_title', '60 capsules')
            ->where('product.data.variants.1.display_title', '120 capsules')
        );
});

it('returns 404 for a draft product', function () {
    $product = Product::factory()->draft()->create(['slug' => 'secret']);
    ProductVariant::factory()->for($product)->create();

    $this->get('/products/secret')->assertNotFound();
});

it('returns 404 for an unknown product slug', function () {
    $this->get('/products/does-not-exist')->assertNotFound();
});

it('searches products by title', function () {
    ProductVariant::factory()->for(Product::factory()->create(['title' => 'Ibuprofen Gel']))->create();
    ProductVariant::factory()->for(Product::factory()->create(['title' => 'Vitamin D Drops']))->create();

    $this->get('/products?search=Ibuprofen')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.title', 'Ibuprofen Gel')
        );
});

it('sorts products by custom position by default', function () {
    ProductVariant::factory()->for(Product::factory()->create(['title' => 'Later', 'position' => 2]))->create();
    ProductVariant::factory()->for(Product::factory()->create(['title' => 'Earlier', 'position' => 1]))->create();

    $this->get('/products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.sort', 'custom')
            ->where('products.data.0.title', 'Earlier')
            ->where('products.data.1.title', 'Later')
        );
});

it('sorts collection products by the custom catalog order', function () {
    $collection = Collection::factory()->create(['slug' => 'featured']);

    $second = Product::factory()->create(['title' => 'Second', 'position' => 2]);
    $first = Product::factory()->create(['title' => 'First', 'position' => 1]);
    ProductVariant::factory()->for($second)->create();
    ProductVariant::factory()->for($first)->create();

    $collection->products()->attach([$second->id, $first->id]);

    $this->get('/collections/featured')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.title', 'First')
            ->where('products.data.1.title', 'Second')
        );
});

it('sorts products by price ascending', function () {
    ProductVariant::factory()->for(Product::factory()->create(['title' => 'Expensive']))->priced(5000)->create();
    ProductVariant::factory()->for(Product::factory()->create(['title' => 'Cheap']))->priced(500)->create();

    $this->get('/products?sort=price_asc')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('products.data.0.title', 'Cheap'));
});

it('filters to products that are in stock', function () {
    ProductVariant::factory()->for(Product::factory()->create(['title' => 'Available']))->withStock(5)->create();
    ProductVariant::factory()->for(Product::factory()->create(['title' => 'Sold out']))->outOfStock()->create();

    $this->get('/products?in_stock=1')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.title', 'Available')
        );
});

it('rejects an unknown sort value', function () {
    $this->get('/products?sort=bogus')->assertSessionHasErrors('sort');
});

it('lists published collections', function () {
    Collection::factory()->create(['title' => 'Cold and Flu']);
    Collection::factory()->draft()->create(['title' => 'Hidden']);

    $this->get('/collections')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/collections/index')
            ->has('collections.data', 1)
            ->where('collections.data.0.title', 'Cold and Flu')
        );
});

it('shows only published products inside a collection', function () {
    $collection = Collection::factory()->create(['slug' => 'daily-care']);

    $live = Product::factory()->create(['title' => 'Live']);
    ProductVariant::factory()->for($live)->create();

    $draft = Product::factory()->draft()->create(['title' => 'Draft']);
    ProductVariant::factory()->for($draft)->create();

    $collection->products()->attach([$live->id, $draft->id]);

    $this->get('/collections/daily-care')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/collections/show')
            ->where('collection.data.slug', 'daily-care')
            ->has('products.data', 1)
            ->where('products.data.0.title', 'Live')
        );
});

it('returns 404 for a draft collection', function () {
    Collection::factory()->draft()->create(['slug' => 'unpublished']);

    $this->get('/collections/unpublished')->assertNotFound();
});

it('shows a "from" price when a product has several variants', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->priced(2000)->create();
    ProductVariant::factory()->for($product)->priced(800)->create();

    $this->get('/products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.price_from.formatted', '$8.00')
            ->where('products.data.0.variant_count', 2)
        );
});
