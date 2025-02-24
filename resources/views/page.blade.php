<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<?php
$url = 'http://8.210.215.190/index.php/api/v1/admin/links';
$options = [
    'http' => [
        'method'    => 'GET',
    ]
];
$content = stream_context_create($options);
echo $links = file_get_contents($url, false, $content);
?>
</body>
</html>
