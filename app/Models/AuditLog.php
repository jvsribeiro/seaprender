<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['actor_id', 'classroom_id', 'action', 'subject_type', 'subject_id', 'metadata', 'ip_address'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const LABELS = [
        'auth.login' => 'Entrou no sistema',
        'auth.failed' => 'Errou a senha',
        'classroom.viewed' => 'Abriu a turma',
        'classroom.created' => 'Turma criada',
        'classroom.updated' => 'Turma editada',
        'classroom.status_changed' => 'Situação da turma alterada',
        'classroom.code_regenerated' => 'Código da turma trocado',
        'enrollment.joined' => 'Aluno entrou na turma',
        'enrollment.requested' => 'Solicitou inscrição na turma',
        'enrollment.approved' => 'Aprovou a inscrição',
        'enrollment.rejected' => 'Recusou a inscrição',
        'enrollment.added' => 'Aluno matriculado',
        'enrollment.removed' => 'Aluno removido',
        'enrollment.imported' => 'Importação de alunos',
        'post.published' => 'Publicou no mural',
        'post.removed' => 'Removeu do mural',
        'post.downloaded' => 'Baixou um arquivo',
        'quiz.saved' => 'Salvou um quiz',
        'quiz.published' => 'Publicou um quiz',
        'quiz.answered' => 'Respondeu um quiz',
        'quiz.removed' => 'Excluiu um quiz',
        'user.role_changed' => 'Perfil alterado',
        'user.password_reset' => 'Senha temporária gerada',
        'user.password_changed' => 'Trocou a senha',
        'user.deleted' => 'Conta excluída',
        'post.reflection_submitted' => 'Enviou uma reflexão',
        'post.participation_closed' => 'Encerrou uma participação',
        'reward.created' => 'Publicou um brinde',
        'reward.updated' => 'Alterou a disponibilidade de um brinde',
        'reward.points_awarded' => 'Concedeu pontos',
        'reward.redeemed' => 'Solicitou um resgate',
        'reward.delivered' => 'Confirmou a entrega do brinde',
        'reward.cancelled' => 'Cancelou um resgate',
        'lesson.drawn' => 'Sorteou um aluno',
        'lesson.teams_drawn' => 'Sorteou grupos',
        'live_quiz.opened' => 'Abriu a sala do quiz ao vivo',
        'live_quiz.started' => 'Começou o quiz ao vivo',
        'live_quiz.finished' => 'Encerrou o quiz ao vivo',
        'live_quiz.game_saved' => 'Salvou um jogo do Se Pergunta',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
