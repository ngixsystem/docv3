<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('manage-users', fn (User $user) => $user->canManageUsers());
        Gate::define('manage-structure', fn (User $user) => $user->canManageStructure());
        Gate::define('create-tasks', fn (User $user) => $user->canCreateTasks());
        Gate::define('register-documents', fn (User $user) => $user->canRegisterDocuments());
        Gate::define('approve-documents', fn (User $user) => $user->canApproveDocuments());
        Gate::define('create-document-type', fn (User $user, string $type) => $user->canCreateDocumentType($type));
        Gate::define('view-document', fn (User $user, Document $document) => $user->canViewDocument($document));
        Gate::define('view-task', fn (User $user, Task $task) => $user->canViewTask($task));

        View::composer('*', function ($view) {
            $view->with('currentUser', Auth::user());
        });
    }
}
