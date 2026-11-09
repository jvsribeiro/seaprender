<?php

use App\Http\Controllers\PostAttachmentController;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\TrackLastSeen;
use App\Livewire\Account\ChangePassword;
use App\Livewire\Admin\AuditLogs;
use App\Livewire\Admin\Users;
use App\Livewire\Admin\UserShow;
use App\Livewire\Classrooms\Browse;
use App\Livewire\Classrooms\Form;
use App\Livewire\Classrooms\Index;
use App\Livewire\Classrooms\Invite;
use App\Livewire\Classrooms\Show;
use App\Livewire\Dashboard;
use App\Livewire\QuestionGames\Editor as QuestionGameEditor;
use App\Livewire\Quizzes\Editor;
use App\Livewire\Quizzes\LiveRoom;
use App\Livewire\Quizzes\Play;
use App\Livewire\Quizzes\Results;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing', ['tab' => 'register'])->middleware('guest')->name('home');

Route::middleware(['auth', TrackLastSeen::class, RequirePasswordChange::class])->group(function () {
    Route::livewire('/painel', Dashboard::class)->name('dashboard');
    Route::livewire('/trocar-senha', ChangePassword::class)->name('password.change');

    Route::livewire('/turmas', Index::class)->name('classrooms.index');
    Route::livewire('/turmas/criar', Form::class)->name('classrooms.create');
    Route::livewire('/turmas/procurar', Browse::class)->name('classrooms.browse');
    Route::livewire('/turmas/{classroom}', Show::class)->whereNumber('classroom')->name('classrooms.show');
    Route::livewire('/turmas/{classroom}/editar', Form::class)->whereNumber('classroom')->name('classrooms.edit');
    Route::livewire('/turmas/{classroom}/quizzes/criar', Editor::class)->whereNumber('classroom')->name('quizzes.create');
    Route::livewire('/turmas/{classroom}/se-pergunta/criar', QuestionGameEditor::class)->whereNumber('classroom')->name('question-games.create');
    Route::livewire('/turmas/{classroom}/se-pergunta/{questionGame}/editar', QuestionGameEditor::class)->whereNumber(['classroom', 'questionGame'])->name('question-games.edit');
    Route::livewire('/convite/{code}', Invite::class)->name('invite');

    Route::livewire('/quizzes/{quiz}', Play::class)->whereNumber('quiz')->name('quizzes.show');
    Route::livewire('/quizzes/{quiz}/editar', Editor::class)->whereNumber('quiz')->name('quizzes.edit');
    Route::livewire('/quizzes/{quiz}/resultados', Results::class)->whereNumber('quiz')->name('quizzes.results');
    Route::livewire('/ao-vivo/{liveQuiz}', LiveRoom::class)->whereNumber('liveQuiz')->name('quizzes.live');

    Route::get('/publicacoes/{post}/arquivo', PostAttachmentController::class)->whereNumber('post')->name('posts.attachment');

    Route::middleware('can:administer')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/usuarios', Users::class)->name('users');
        Route::livewire('/usuarios/{user}', UserShow::class)->whereNumber('user')->name('users.show');
        Route::livewire('/auditoria', AuditLogs::class)->name('audit');
    });
});
