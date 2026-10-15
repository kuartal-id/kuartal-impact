<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EntryController as AdminEntryController;
use App\Http\Controllers\Admin\MediaLibraryController as AdminMediaController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\IndexCampaignController as AdminIndexCampaignController;
use App\Http\Controllers\Admin\IndexOrganisationController as AdminIndexOrgController;
use App\Http\Controllers\Admin\IndexTaxonomyController as AdminIndexTaxonomyController;
use App\Http\Controllers\Auth\KuartalIdLoginController;
use App\Http\Controllers\ImpactIndexController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NewsroomController;
use App\Http\Controllers\SiteController;
use App\Http\Middleware\RefreshKuartalIdEntitlements;
use Illuminate\Support\Facades\Route;

/*
| kuartalimpact.org: public pages, newsroom, and /admin (Kuartal ID +
| the `kuartalimpact.admin` entitlement).
*/

Route::get('/auth/kuartal-id/redirect', [KuartalIdLoginController::class, 'redirect'])->name('login.kuartal-id');
Route::get('/auth/kuartal-id/callback', [KuartalIdLoginController::class, 'callback'])->name('login.kuartal-id.callback');
Route::post('/logout', [KuartalIdLoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/about', [SiteController::class, 'about'])->name('about');
Route::get('/focus-areas', [SiteController::class, 'focusAreas'])->name('focus-areas');
Route::get('/portfolio', [SiteController::class, 'portfolio'])->name('portfolio');
Route::get('/programmes', [SiteController::class, 'programmes'])->name('programmes.index');
Route::get('/programmes/{slug}', [SiteController::class, 'programme'])->where('slug', '[A-Za-z0-9_-]+')->name('programmes.show');
Route::redirect('/funds', '/programmes', 301);
Route::get('/contact', [SiteController::class, 'contact'])->name('contact');
Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');

// Kuartal Impact Index (public).
Route::redirect('/index', '/impact-index', 301);
Route::prefix('impact-index')->name('impact-index')->group(function () {
    Route::get('/', [ImpactIndexController::class, 'index']);
    Route::get('/methodology', [ImpactIndexController::class, 'methodology'])->name('.methodology');
    Route::get('/rankings', [ImpactIndexController::class, 'rankings'])->name('.rankings');
    Route::get('/sector/{sector:slug}', [ImpactIndexController::class, 'sector'])->name('.sector');
    Route::get('/region/{region:slug}', [ImpactIndexController::class, 'region'])->name('.region');
    Route::get('/campaigns', [ImpactIndexController::class, 'campaigns'])->name('.campaigns');
    Route::get('/campaigns/{campaign:slug}', [ImpactIndexController::class, 'campaign'])->name('.campaign');
    Route::get('/org/{organisation:slug}', [ImpactIndexController::class, 'show'])->name('.show');
});

// Old WordPress links (dead on the previous site) land somewhere useful.
Route::redirect('/how-we-invest', '/about#how-we-invest', 301);
Route::redirect('/our-team', '/about#team', 301);

// Newsroom (admin-managed posts) + RSS.
Route::get('/newsroom', [NewsroomController::class, 'index'])->name('newsroom.index');
Route::get('/newsroom/feed', [NewsroomController::class, 'feed'])->name('newsroom.feed');
Route::get('/newsroom/{post}', [NewsroomController::class, 'show'])->name('newsroom.show');

// Public media-library files (served from the private disk).
Route::get('/media/{media}/{name?}', [MediaController::class, 'show'])
    ->middleware('throttle:240,1')->name('media.show');

Route::middleware(['auth', RefreshKuartalIdEntitlements::class, 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
    Route::get('/posts/new', [AdminPostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [AdminPostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post:id}/edit', [AdminPostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post:id}', [AdminPostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post:id}', [AdminPostController::class, 'destroy'])->name('posts.destroy');

    Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
    Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
    Route::put('/media/{media:id}', [AdminMediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{media:id}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/media/{media:id}/file', [MediaController::class, 'admin'])->name('media.file');

    Route::get('/content', [AdminEntryController::class, 'index'])->name('entries.index');
    Route::get('/content/new', [AdminEntryController::class, 'create'])->name('entries.create');
    Route::post('/content', [AdminEntryController::class, 'store'])->name('entries.store');
    Route::get('/content/{entry}/edit', [AdminEntryController::class, 'edit'])->name('entries.edit');
    Route::put('/content/{entry}', [AdminEntryController::class, 'update'])->name('entries.update');
    Route::delete('/content/{entry}', [AdminEntryController::class, 'destroy'])->name('entries.destroy');

    // Impact Index.
    Route::prefix('index')->name('index.')->group(function () {
        Route::get('/organisations', [AdminIndexOrgController::class, 'index'])->name('orgs.index');
        Route::get('/organisations/new', [AdminIndexOrgController::class, 'create'])->name('orgs.create');
        Route::post('/organisations', [AdminIndexOrgController::class, 'store'])->name('orgs.store');
        Route::get('/organisations/{org:id}/edit', [AdminIndexOrgController::class, 'edit'])->name('orgs.edit');
        Route::put('/organisations/{org:id}', [AdminIndexOrgController::class, 'update'])->name('orgs.update');
        Route::delete('/organisations/{org:id}', [AdminIndexOrgController::class, 'destroy'])->name('orgs.destroy');
        Route::get('/organisations/{org:id}/scores', [AdminIndexOrgController::class, 'scores'])->name('orgs.scores');
        Route::put('/organisations/{org:id}/scores', [AdminIndexOrgController::class, 'saveScores'])->name('orgs.scores.save');

        Route::get('/settings', [AdminIndexTaxonomyController::class, 'index'])->name('settings');
        Route::put('/weights', [AdminIndexTaxonomyController::class, 'saveWeights'])->name('weights');
        Route::post('/sectors', [AdminIndexTaxonomyController::class, 'storeSector'])->name('sectors.store');
        Route::put('/sectors/{sector:id}', [AdminIndexTaxonomyController::class, 'updateSector'])->name('sectors.update');
        Route::delete('/sectors/{sector:id}', [AdminIndexTaxonomyController::class, 'destroySector'])->name('sectors.destroy');
        Route::post('/regions', [AdminIndexTaxonomyController::class, 'storeRegion'])->name('regions.store');
        Route::put('/regions/{region:id}', [AdminIndexTaxonomyController::class, 'updateRegion'])->name('regions.update');
        Route::delete('/regions/{region:id}', [AdminIndexTaxonomyController::class, 'destroyRegion'])->name('regions.destroy');

        Route::get('/campaigns', [AdminIndexCampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/new', [AdminIndexCampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [AdminIndexCampaignController::class, 'store'])->name('campaigns.store');
        Route::get('/campaigns/{campaign:id}/edit', [AdminIndexCampaignController::class, 'edit'])->name('campaigns.edit');
        Route::put('/campaigns/{campaign:id}', [AdminIndexCampaignController::class, 'update'])->name('campaigns.update');
        Route::delete('/campaigns/{campaign:id}', [AdminIndexCampaignController::class, 'destroy'])->name('campaigns.destroy');
    });

});
