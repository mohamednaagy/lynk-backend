<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Wakala</title>
</head>
<body>
<header>{{$header}}</header>
<h1>Company Wakala - {{ $companyName }} - {{ $crNumber }} - {{ now()->toDateTimeString() }}</h1>
<footer>{{$footer}}</footer>
</body>
</html>
