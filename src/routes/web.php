<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\RegistryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::get('/files/{file}/inline/shared', [FileController::class, 'inline'])
    ->middleware('signed')
    ->name('files.inline.shared');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::post('/profile/background', [UserController::class, 'updateBackground'])->name('profile.background.update');
    Route::delete('/profile/background', [UserController::class, 'destroyBackground'])->name('profile.background.destroy');

    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/drafts', [DocumentController::class, 'drafts'])->name('drafts');
        Route::get('/create', [DocumentController::class, 'create'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}/edit', [DocumentController::class, 'edit'])->name('edit');
        Route::match(['put', 'patch'], '/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::patch('/{document}/status', [DocumentController::class, 'updateStatus'])->name('status');
        Route::patch('/{document}/executor-complete', [DocumentController::class, 'completeExecutor'])->name('executor-complete');
        Route::post('/{document}/comment', [DocumentController::class, 'addComment'])->name('comment');
        Route::post('/{document}/upload', [FileController::class, 'upload'])->name('upload');
    });

    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/{task}', [TaskController::class, 'show'])->name('show');
        Route::patch('/{task}/status', [TaskController::class, 'updateStatus'])->name('status');
        Route::post('/{task}/comment', [TaskController::class, 'addComment'])->name('comment');
        Route::post('/{task}/upload', [TaskController::class, 'uploadFile'])->name('upload');
    });

    Route::get('/task-files/{taskFile}/download', [TaskController::class, 'downloadFile'])->name('task-files.download');

    Route::prefix('registry')->name('registry.')->group(function () {
        Route::get('/', [RegistryController::class, 'index'])->name('index');
        Route::post('/', [RegistryController::class, 'store'])->name('store');
        Route::patch('/{entry}/pin', [RegistryController::class, 'togglePin'])->name('pin');
        Route::delete('/{entry}', [RegistryController::class, 'destroy'])->name('destroy');

        Route::middleware('role:admin')->prefix('access')->name('access.')->group(function () {
            Route::get('/', [RegistryController::class, 'accessIndex'])->name('index');
            Route::post('/grant', [RegistryController::class, 'grantAccess'])->name('grant');
            Route::delete('/{access}', [RegistryController::class, 'revokeAccess'])->name('revoke');
        });
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('/files/{file}/view', [FileController::class, 'view'])->name('files.view');
    Route::get('/files/{file}/inline', [FileController::class, 'inline'])->name('files.inline');

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::get('/organization', [DepartmentController::class, 'index'])->name('organization.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::match(['put', 'patch'], '/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
        Route::match(['put', 'patch'], '/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
        Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

        Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::match(['put', 'patch'], '/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    });
});
