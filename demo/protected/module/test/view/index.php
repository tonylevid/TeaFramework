<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Hello, <?php echo $name; ?>. 您好，<?php echo $name; ?>。</title>
</head>
<body>
    <?php echo $pager; ?>
    <?php var_dump($sqlAll); ?>
    <?php var_dump($data); ?>
    <form action="/GitHub/TeaFramework/demo/test/main/upload" method="post" enctype="multipart/form-data">
        <p>Pictures:
        <input type="file" name="pictures[]" />
        <input type="file" name="pictures[]" />
        <input type="file" name="pictures[]" />
        <input type="submit" value="Send" />
        </p>
    </form>
</body>
</html>