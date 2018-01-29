<?php echo $first_name; ?>:

You've started the process to reset your password for <?php echo PROJECT_NAME; ?>.

Click on the link below or copy and paste it into your browser address bar.
<?php echo ABSURL; ?>password/reset?email=<?php echo $email; ?>&token=<?php echo $token; ?>


On the page that loads, you'll be able to enter a new password for the site.

Sincerely,
<?php echo PROJECT_NAME; ?>
