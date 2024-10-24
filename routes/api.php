<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\CaptchaController;
use App\Http\Controllers\Api\Auth\TwoFaController;
use App\Http\Controllers\Api\BlogPostController;
use App\Http\Controllers\Api\ChartStoreController;
use App\Http\Controllers\Api\CheapCountryController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\FxDataController;
use App\Http\Controllers\Api\MigrateController;
use App\Http\Controllers\Api\MiscellaneousController;
use App\Http\Controllers\Api\PairController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\PostCommentController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\StrategyController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TestSessionController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserExplicitController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\NotFoundController;
use App\Http\Controllers\DevopsController;
use App\Http\Middlewares\AuthMiddleware;
use App\Http\Middlewares\DevopsGuardMiddleware;
use App\Http\Middlewares\RoleMiddleware;
use App\Http\Middlewares\ThrottleRequestsMiddleware;
use App\Http\Middlewares\VisitLoggerMiddleware;
use App\Http\Middlewares\WebAuthMiddleware;
use Eyika\Atom\Framework\Http\Route;

Route::middleware(VisitLoggerMiddleware::class, function () {
    /// Auth routes
    Route::group('/auth', function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/login', [AuthController::class, 'loginOauth']);
        Route::get('/captcha', [CaptchaController::class, 'generate']);
        Route::post('/captcha', [CaptchaController::class, 'comparePhrase']);
        Route::middleware(AuthMiddleware::class, function () {
            Route::get('/refresh-token', [AuthController::class, 'refreshToken']);
            Route::get('/twofa/mode/$mode', [TwoFaController::class, 'generate']);
            Route::post('/twofa/mode/$mode', [TwoFaController::class, 'verifyCode']);
            Route::get('/twofa/mode/$mode/status/$status', [TwoFaController::class, 'twofaonoff']);
        });
    });
    /// User Routes
    Route::group('/users', function () {
        Route::post('', [UserController::class, 'create']);
        /// User Special Routes
        Route::post('/method/$method', [UserExplicitController::class, 'index']);
        Route::middleware(AuthMiddleware::class, function () {
            Route::get('/$user', [UserController::class, 'show'])->middleware([ThrottleRequestsMiddleware::class, "3,60"]);
            Route::get('', [UserController::class, 'list'])->name('users.list')->middleware([[ThrottleRequestsMiddleware::class, "3,60"]]);
            Route::get('/query', [UserController::class, 'list']);
            Route::put('/$user', [UserController::class, 'update'])->middleware([ThrottleRequestsMiddleware::class]);
            Route::delete('/$user', [UserController::class, 'delete'])->middleware(ThrottleRequestsMiddleware::class);
            Route::post('/affilliate/withdraw/$points', [UserController::class, 'withrawAffilliateEarnings']);
        });
    });
    /// Plan Routes
    Route::group('/plans', function () {
        Route::get('/$plan', [PlanController::class, 'show']);
        Route::get('', [PlanController::class, 'list'])->middleware([[ThrottleRequestsMiddleware::class]]);
        Route::get('/standard/$standard', [PlanController::class, 'list']);
        Route::get('/standard', [PlanController::class, 'list']);
        Route::get('/query', [PlanController::class, 'list']);
        Route::middleware(AuthMiddleware::class, function () {
            Route::post('', [PlanController::class, 'create']);
            Route::put('/$plan', [PlanController::class, 'update']);
            Route::delete('/$plan', [PlanController::class, 'delete']);
        });
    });
    /// Subscription Routes
    Route::group('/subscriptions', function () {
        Route::middleware(AuthMiddleware::class, function () {
            Route::get('/$subscription', [SubscriptionController::class, 'show']);
            // Route::post('/subscriptions', new SubscriptionController('create'));
            Route::put('/$subscription/cancel', [SubscriptionController::class, 'cancel'])->middleware([RoleMiddleware::class, 'user']);
        });
    });
    /// Transaction Routes
    Route::group('/transactions', function () {
        Route::get('/$transaction', [TransactionController::class, 'show']);
        Route::post('/webhook/post', [TransactionController::class, 'update']);
        Route::middleware(AuthMiddleware::class, function () {
            Route::get('', [TransactionController::class, 'list'])->name('transactions.list');
            Route::post('/verify', [TransactionController::class, 'create']);
            Route::post('/generate/ref', [TransactionController::class, 'generateTxRef']);
        });
    });
    /// Strategy Routes
    Route::group('/strategies', function () {
        Route::middleware(AuthMiddleware::class, function () {
            Route::get('/$strategy', [StrategyController::class, 'show']);
            Route::get('', [StrategyController::class, 'list']);
            Route::get('/query', [StrategyController::class, 'list']);
            Route::get('/users/$id', [StrategyController::class, 'listUser']);
            Route::post('', [StrategyController::class, 'create'])->middleware([RoleMiddleware::class, 'user']);
            Route::put('/$strategy', [StrategyController::class, 'update'])->middleware([RoleMiddleware::class, 'user']);
            Route::delete('/$strategy', [StrategyController::class, 'delete']);
        });
    });
    /// TestSessions Routes
    Route::group('/test-sessions', function () {
        Route::middleware(AuthMiddleware::class, function () {
            Route::get('/$testsession', [TestSessionController::class, 'show']);
            Route::get('', [TestSessionController::class, 'list']);
            Route::get('/query', [TestSessionController::class, 'list']);
            Route::middleware([RoleMiddleware::class, "user"], function () {
                Route::post('', [TestSessionController::class, 'create']);
                Route::put('/$testsession', [TestSessionController::class, 'update']);
                Route::delete('/$testsession', [TestSessionController::class, 'delete']);
            });
        });
    });
    /// Pairs Routes
    Route::group('/pairs', function () {
        Route::get('/$pair', [PairController::class, 'show']);
        Route::get('', [PairController::class, 'list']);
        Route::get('/list/onlypair', [PairController::class, 'listonlypair']);
        Route::get('/list/pairorsym/$info_select/query', [PairController::class, 'query']);
        Route::middleware(AuthMiddleware::class, function () {
            Route::post('', [PairController::class, 'create']);
            Route::put('/$pair', [PairController::class, 'update']);
            Route::delete('/$pair', [PairController::class, 'delete']);
        });
    });
    Route::group('/positions', function () {
        /// Positions Routes
        Route::middleware(AuthMiddleware::class, function () {
            Route::get('/$position', [PositionController::class, 'show']);
            Route::get('', [PositionController::class, 'list'])->name('positions.list');
            Route::get('/query', [PositionController::class, 'list'])->name('positions.query');
            Route::get('/users/$id', [PositionController::class, 'list_user']);
            Route::post('', [PositionController::class, 'create']);
            Route::put('/$position', [PositionController::class, 'update']);
            Route::put('/$position/unset/$tporsl', [PositionController::class, 'unsetslortp']);
            Route::delete('/$position', [PositionController::class, 'delete']);
        });
    });
    /// Feedbacks Routes
    Route::group('/feedbacks', function () {
        Route::middleware(AuthMiddleware::class, function () {
            Route::get('/$feedback', [FeedbackController::class, 'show']);
            Route::get('', [FeedbackController::class, 'list'])->name('feedbacks.list');
            Route::get('/query', [FeedbackController::class, 'list'])->name('feedbacks.query');
            Route::get('/users/$feedback', [FeedbackController::class, 'list_user']);
            Route::post('', [FeedbackController::class, 'create']);
            Route::put('/$feedback', [FeedbackController::class, 'update']);
            Route::delete('/$feedback', [FeedbackController::class, 'delete']);
        });
    });
    /// Admin Routes
    Route::group('/admin', function () {
        Route::middleware([[AuthMiddleware::class], [RoleMiddleware::class, 'admin']], function () {
            Route::group('/visit-logs', function () {
                Route::get('/$visit', [VisitController::class, 'show']);
                Route::get('/all/count', [VisitController::class, 'count']);
                Route::get('/all/count', [VisitController::class, 'count'])->name('admin.visit-logs.countquery');
                Route::get('', [VisitController::class, 'list'])->name('admin.visit-logs.list');
                Route::get('/query', [VisitController::class, 'list']);
            });
            Route::group('/blog-posts', function () {
                Route::post('', [BlogPostController::class, 'create']);
                Route::put('/$blog', [BlogPostController::class, 'update']);
                Route::delete('/$blog', [BlogPostController::class, 'delete']);
                Route::get('/comments', [PostCommentController::class, 'listall'])->name('admin.blog.comments.list');
            });
            Route::group('/subscriptions', function () {
                Route::get('/all/count', [SubscriptionController::class, 'count']);
                // Route::get('/all/count', [SubscriptionController::class, 'count']);
                Route::get('', [SubscriptionController::class, 'list'])->name('admin.subscriptions.list');
                Route::get('/query', [SubscriptionController::class, 'list'])->name('admin.subscriptions.query');
                Route::get('/users/$id', [SubscriptionController::class, 'listUser'])->name('admin.subscriptions.listuser');
                Route::get('/plans/$id', [SubscriptionController::class, 'listPlan'])->name('admin.subscriptions.listplan');
            });
            Route::group('/cheapcountries', function () {
                Route::get('/$cheapcountry', [CheapCountryController::class, 'show']);
                Route::get('', [CheapCountryController::class, 'list']);
                Route::post('', [CheapCountryController::class, 'create']);
                Route::put('/$cheapcountry', [CheapCountryController::class, 'update']);
                Route::delete('/$cheapcountry', [CheapCountryController::class, 'delete']);
            });
            Route::post('/landing/data', [MiscellaneousController::class, 'updateLanding']);
        });
    });
    Route::middleware(DevopsGuardMiddleware::class, function () {
        Route::group('/devops', function () {
            Route::get('/login', [DevopsController::class, 'viewLogin']);
            Route::post('/login', [DevopsController::class, 'login']);
            Route::get('/logout', [DevopsController::class, 'logout']);
            Route::middleware(WebAuthMiddleware::class, function() {
                Route::get('/migrate/status', [MigrateController::class, 'status']);
                Route::get('/migrate/migrate', [MigrateController::class, 'migrate']);
                Route::get('/migrate/rollback', [MigrateController::class, 'rollback']);
                Route::get('/migrate/seed', [MigrateController::class, 'seed']);
                Route::get('/error-logs', [DevopsController::class, 'logViewer']);
            });
        });
    });
    Route::group('/blog-posts', function () {
        Route::get('', [BlogPostController::class, 'list'])->name('blog-posts.list');
        Route::get('/$blog', [BlogPostController::class, 'show']);
        Route::get('/comments/$id', [PostCommentController::class, 'show']);
        Route::get('/comments/query', [PostCommentController::class, 'list'])->name('blog-posts.comments.query');
        Route::get('/$id/comments', [PostCommentController::class, 'list'])->name('blog-posts.comments.list');
        Route::post('/$id/comments', [PostCommentController::class, 'create']);
        Route::middleware([AuthMiddleware::class], function () {
            Route::put('/comments/$post', [PostCommentController::class, 'update']);
            Route::delete('/comments/$post', [PostCommentController::class, 'delete']);
        });
    });
    /// Historical Data Routes
    Route::group('/fx', function () {
        Route::get('/download/min/ticker/$ticker/offerside/$offerside/period/$period/yr/$year/mn/$month/wk/$week', [FxDataController::class, 'downloadMinutesData']);
        Route::get('/currency/conversiondata/ticker/$ticker/year/$year', [FxDataController::class, 'currencyConversionData']);
        Route::get('/tickers/query', [MiscellaneousController::class, 'searchTicker']);
        Route::get('/tickers/query', [MiscellaneousController::class, 'searchTicker']);
    });
    Route::middleware(AuthMiddleware::class, false)->group('/chartstore', function () {
        Route::group('chart_layouts', function () {
            Route::get('', [ChartStoreController::class, 'getAllCharts']);
            Route::get('/$id', [ChartStoreController::class, 'getChartContent']);
            Route::post('', [ChartStoreController::class, 'saveChart']);
            Route::put('/$id', [ChartStoreController::class, 'updateChart']);
            Route::delete('/$id', [ChartStoreController::class, 'removeChart']);
        });
        Route::group('chart_templates', function () {
            Route::get('/name', [ChartStoreController::class, 'getAllChartTemplates']);
            Route::get('/name/$name', [ChartStoreController::class, 'getChartTemplateContent']);
            Route::post('', [ChartStoreController::class, 'saveChartTemplate']);
            Route::delete('/name/$name', [ChartStoreController::class, 'removeChartTemplate']);
        });
        Route::group('study_templates', function () {
            Route::get('/name', [ChartStoreController::class, 'getAllStudyTemplates']);
            Route::get('/name/$name', [ChartStoreController::class, 'getStudyTemplateContent']);
            Route::post('', [ChartStoreController::class, 'saveStudyTemplate']);
            Route::delete('/name/$name', [ChartStoreController::class, 'removeStudyTemplate']);
        });
        Route::group('drawing_templates', function () {
            Route::get('/templateName', [ChartStoreController::class, 'getDrawingTemplates']);
            Route::get('/toolname/$toolName/templatename/$templateName', [ChartStoreController::class, 'loadDrawingTemplate']);
            Route::post('', [ChartStoreController::class, 'saveDrawingTemplate']);
            Route::delete('/toolname/$toolName/templatename/$templateName', [ChartStoreController::class, 'removeDrawingTemplate']);
        });
        Route::group('line_and_group_tools', function () {
            Route::get('/layout_id/$layoutid/chart_id/$chartid', [ChartStoreController::class, 'loadLineToolsAndGroups']);
            Route::post('', [ChartStoreController::class, 'saveLineToolsAndGroups']); 
        });
    });
    /// Others
    Route::post('/misc/contact-us', [MiscellaneousController::class, 'contact_us'])->name('contact-us');
    Route::get('/landing/data', [MiscellaneousController::class, 'fetchLanding']);
    Route::get('/referrals/$referral', [ReferralController::class, 'show']);
    Route::get('/referrals', [ReferralController::class, 'list'])->middleware(AuthMiddleware::class)->name('referrals.list');

    Route::any('/404', [NotFoundController::class, 'index']);
});
