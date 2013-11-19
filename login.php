<!DOCTYPE html>
<html>
<head>
<title>CSSA Vote</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.css">
<script src="bootstrap/js/bootstrap.js"></script>
</head>
<body>
<div style="text-align:center">
<img src="img/cssa_logo.png">
<h1>CSSA Voting</h1>

<?php
if (isset($_GET['message'])) {
?>
<div class="alert" style="width:300px;margin:0 auto"><b><?php echo $_GET['message']; ?></b></div>
<?php
}
?>

<h3>Please enter your administrator credentials below.</h3>
<form action="admin.php" method="POST">
<label>Username:</label> <input type="text" name="username" length="10" maxlength="32"><br><br>
<label>Password:</label> <input type="password" name="password" length="10"><br>
<input type="submit">
</form>
</div>
</body>
</html>