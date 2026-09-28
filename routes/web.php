<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AgentController as AdminAgentController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CommunityModerationController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\FarmerController;
use App\Http\Controllers\Admin\MarketController as AdminMarketController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Community\CommentController as CommunityCommentController;
use App\Http\Controllers\Community\FeedController as CommunityFeedController;
use App\Http\Controllers\Community\PostController as CommunityPostController;
use App\Http\Controllers\Customer\AgentController as CustomerAgentController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\ConversationController as CustomerConversationController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\FarmerController as CustomerFarmerController;
use App\Http\Controllers\Customer\FavouriteController;
use App\Http\Controllers\Customer\MarketController;
use App\Http\Controllers\Customer\MessageController as CustomerMessageController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Customer\ReviewController as CustomerReviewController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Farmer\AgentController as FarmerAgentController;
use App\Http\Controllers\Farmer\ConversationController as FarmerConversationController;
use App\Http\Controllers\Farmer\FarmerDashboardController;
use App\Http\Controllers\Farmer\InsightsController;
use App\Http\Controllers\Farmer\OrderController;
use App\Http\Controllers\Farmer\PickupWindowController;
use App\Http\Controllers\Farmer\ProductController;
use App\Http\Controllers\Farmer\ReviewController;
use App\Http\Controllers\Farmer\StallProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\VoiceNoteController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/about', [PagesController::class, 'about'])->name('about');
Route::get('/contact', [PagesController::class, 'contact'])->name('contact');

// chat voice notes sahi audio/webm Content-Type ke saath. filename constraint sirf hamara
// hashed naam + extension allow karta hai, folder se bahar nahi ja sakte
Route::get('/voice-notes/{filename}', [VoiceNoteController::class, 'show'])
    ->where('filename', '[A-Za-z0-9]+\.(webm|mp4|m4a|ogg|mp3|wav)')
    ->name('voice-notes.show');

// community feed - approved posts sab parh sakte hain, guest bhi. Post / like / comment ke liye account chahiye
Route::get('/community', [CommunityFeedController::class, 'index'])->name('community.index');

// Ctrl+K command palette - guests ke liye bhi, sirf wahi pages search hote hain jo guest waise bhi dekh sakta hai
Route::get('/search', [SearchController::class, 'index'])->name('search');

// marketplace browse karna sab ke liye khula hai, account se pehle log dekh saken kya mil raha hai.
// basket, checkout, favourites, reviews neeche auth group mein
Route::prefix('customer')->name('customer.')->group(function () {
    Route::prefix('markets')->name('markets.')->controller(MarketController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{market}', 'show')->name('show');
    });

    Route::prefix('products')->name('products.')->controller(CustomerProductController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{product}', 'show')->name('show');
    });

    Route::get('/farmers', [CustomerFarmerController::class, 'index'])->name('farmers.index');
    Route::get('/farmers/{farmer}', [CustomerFarmerController::class, 'show'])->name('farmers.show');
});

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    // Breeze profile routes, sab roles ke liye
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // panel header ki notification bell, har role
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    // community pe post / like / comment - koi bhi logged in role
    Route::prefix('community')->name('community.')->group(function () {
        Route::post('/posts', [CommunityPostController::class, 'store'])->name('posts.store');
        Route::post('/posts/{post}/like', [CommunityPostController::class, 'toggleLike'])->name('posts.like');
        Route::post('/posts/{post}/comments', [CommunityCommentController::class, 'store'])->name('posts.comments.store');
    });

    Route::prefix('customer')->name('customer.')->middleware('role:customer')->group(function () {
        Route::get('/dashboard', CustomerDashboardController::class)->middleware('urgent-auto-ready')->name('dashboard');

        Route::prefix('cart')->name('cart.')->controller(CartController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{product}', 'store')->name('store');
            Route::patch('/{product}', 'update')->name('update');
            Route::delete('/{product}', 'destroy')->name('destroy');
        });

        Route::prefix('checkout')->name('checkout.')->controller(CheckoutController::class)->group(function () {
            Route::get('/{farmer}', 'show')->name('show');
            Route::post('/{farmer}', 'store')->name('store');
        });

        Route::prefix('orders')->name('orders.')->controller(CustomerOrderController::class)->group(function () {
            Route::get('/', 'index')->middleware('urgent-auto-ready')->name('index');
            Route::get('/{order}', 'show')->middleware('urgent-auto-ready')->name('show');
            Route::put('/{order}/quantities', 'updateQuantities')->name('update-quantities');
            Route::post('/{order}/cancel', 'cancel')->name('cancel');
            Route::post('/{order}/reorder', 'reorder')->name('reorder');
        });

        Route::prefix('favourites')->name('favourites.')->controller(FavouriteController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/farmers/{farmer}/toggle', 'toggleFarmer')->name('farmers.toggle');
            Route::post('/products/{product}/toggle', 'toggleProduct')->name('products.toggle');
            Route::patch('/products/{product}/restock-alert', 'updateProductRestockAlert')->name('products.restock-alert');
            Route::post('/markets/{market}/toggle', 'toggleMarket')->name('markets.toggle');
        });

        Route::prefix('reviews')->name('reviews.')->controller(CustomerReviewController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
        });

        // customer kisi bhi dikhne wale farmer ko message kar sakta hai - farmer sirf reply karta hai
        Route::post('/farmers/{farmer}/message', [CustomerMessageController::class, 'store'])->name('farmers.message');

        Route::prefix('messages')->name('messages.')->controller(CustomerConversationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{conversation}', 'show')->name('show');
        });

        Route::prefix('agent')->name('agent.')->controller(CustomerAgentController::class)->group(function () {
            Route::post('/chat', 'chat')->middleware('throttle:agent-chat')->name('chat');
            Route::get('/history', 'history')->name('history');
            Route::get('/unread-count', 'unreadCount')->name('unread-count');
            Route::post('/mark-read', 'markRead')->name('mark-read');
        });
    });

    Route::prefix('farmer')->name('farmer.')->middleware('role:farmer')->group(function () {
        Route::get('/dashboard', FarmerDashboardController::class)->middleware('urgent-auto-ready')->name('dashboard');

        Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::post('/refill-stock', 'refillStock')->name('refill-stock');
            Route::get('/{product}/edit', 'edit')->name('edit');
            Route::put('/{product}', 'update')->name('update');
            Route::patch('/{product}/availability', 'updateAvailability')->name('update-availability');
            Route::delete('/{product}', 'destroy')->name('destroy');
        });

        Route::prefix('pickup-windows')->name('pickup-windows.')->controller(PickupWindowController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{pickupWindow}/edit', 'edit')->name('edit');
            Route::put('/{pickupWindow}', 'update')->name('update');
            Route::patch('/{pickupWindow}/toggle', 'toggleActive')->name('toggle');
            Route::delete('/{pickupWindow}', 'destroy')->name('destroy');
        });

        Route::prefix('orders')->name('orders.')->controller(OrderController::class)->group(function () {
            Route::get('/', 'index')->middleware('urgent-auto-ready')->name('index');
            Route::get('/{order}', 'show')->middleware('urgent-auto-ready')->name('show');
            Route::post('/{order}/accept', 'accept')->name('accept');
            Route::post('/{order}/decline', 'decline')->name('decline');
            Route::post('/{order}/ready', 'markReady')->name('ready');
            Route::post('/{order}/complete', 'markCompleted')->name('complete');
        });

        Route::prefix('reviews')->name('reviews.')->controller(ReviewController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{review}/reply', 'reply')->name('reply');
        });

        Route::get('/insights', InsightsController::class)->name('insights.index');

        Route::prefix('stall')->name('stall.')->controller(StallProfileController::class)->group(function () {
            Route::get('/', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
        });

        // jaan boojh ke "naya conversation" route nahi - farmer sirf reply kar sakta hai
        Route::prefix('messages')->name('messages.')->controller(FarmerConversationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{conversation}', 'show')->name('show');
            Route::post('/{conversation}/reply', 'reply')->name('reply');
        });

        Route::prefix('agent')->name('agent.')->controller(FarmerAgentController::class)->group(function () {
            Route::post('/chat', 'chat')->middleware('throttle:agent-chat')->name('chat');
            Route::get('/history', 'history')->name('history');
            Route::get('/unread-count', 'unreadCount')->name('unread-count');
            Route::post('/mark-read', 'markRead')->name('mark-read');
        });
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        // koi bhi general admin permission ho to khulta hai (Spatie ka "|" = koi bhi) - Community Moderator ke paas koi nahi
        Route::get('/dashboard', AdminDashboardController::class)
            ->middleware('permission:'.implode('|', User::GENERAL_ADMIN_PERMISSIONS))
            ->name('dashboard');

        Route::prefix('farmers')->name('farmers.')->middleware('permission:manage-farmers')->controller(FarmerController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{farmer}', 'show')->name('show');
            Route::post('/{farmer}/approve', 'approve')->name('approve');
            Route::post('/{farmer}/suspend', 'suspend')->name('suspend');
        });

        Route::prefix('customers')->name('customers.')->middleware('permission:manage-customers')->controller(CustomerController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{customer}', 'show')->name('show');
            Route::post('/{customer}/toggle-active', 'toggleActive')->name('toggle-active');
        });

        // markets, categories, announcements aur reports sirf Super Admin - Support Admin ko 403
        Route::prefix('markets')->name('markets.')->middleware('permission:manage-markets')->controller(AdminMarketController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{market}/edit', 'edit')->name('edit');
            Route::put('/{market}', 'update')->name('update');
            Route::patch('/{market}/toggle-active', 'toggleActive')->name('toggle-active');
            Route::delete('/{market}', 'destroy')->name('destroy');
        });

        Route::prefix('categories')->name('categories.')->middleware('permission:manage-categories')->controller(CategoryController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{category}/edit', 'edit')->name('edit');
            Route::put('/{category}', 'update')->name('update');
            Route::delete('/{category}', 'destroy')->name('destroy');
        });

        Route::prefix('moderation')->name('moderation.')->middleware('permission:moderate-content')->controller(ModerationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/products/{product}/hide', 'hideProduct')->name('products.hide');
            Route::post('/products/{product}/unhide', 'unhideProduct')->name('products.unhide');
            Route::post('/reviews/{review}/hide', 'hideReview')->name('reviews.hide');
            Route::post('/reviews/{review}/unhide', 'unhideReview')->name('reviews.unhide');
        });

        Route::prefix('reports')->name('reports.')->middleware('permission:view-reports')->controller(ReportController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/print', 'print')->name('print');
            Route::get('/export-csv', 'exportCsv')->name('export-csv');
        });

        Route::prefix('announcements')->name('announcements.')->middleware('permission:manage-announcements')->controller(AnnouncementController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{announcement}/edit', 'edit')->name('edit');
            Route::put('/{announcement}', 'update')->name('update');
            Route::delete('/{announcement}', 'destroy')->name('destroy');
        });

        // community-moderator ko sirf yahi ek permission milti hai
        Route::prefix('community')->name('community.')->middleware('permission:moderate-community-posts')->controller(CommunityModerationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{post}/approve', 'approve')->name('approve');
            Route::post('/{post}/reject', 'reject')->name('reject');
            Route::post('/{post}/pin', 'pin')->name('pin');
            Route::post('/{post}/unpin', 'unpin')->name('unpin');
            Route::delete('/{post}', 'destroy')->name('destroy');
        });

        // permission middleware jaan boojh ke nahi - widget har admin khol sakta hai, tools toolkit decide karta hai
        Route::prefix('agent')->name('agent.')->controller(AdminAgentController::class)->group(function () {
            Route::post('/chat', 'chat')->middleware('throttle:agent-chat')->name('chat');
            Route::get('/history', 'history')->name('history');
            Route::get('/unread-count', 'unreadCount')->name('unread-count');
            Route::post('/mark-read', 'markRead')->name('mark-read');
        });
    });
});

require __DIR__.'/auth.php';
