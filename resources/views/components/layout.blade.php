@props(['title' => 'Se Aprender'])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="Atividades, entregas e quizzes ao vivo para a sua turma, com XP, moedas e conquistas.">
    <meta name="theme-color" content="#004C94">
    <link rel="icon" href="{{ asset('images/logo-icon.png') }}" type="image/png">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-dvh flex-col overflow-x-hidden bg-azul font-sans text-white antialiased">
    {{ $slot }}
</body>
</html>
