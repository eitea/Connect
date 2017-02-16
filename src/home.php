<?php include 'header.php'; ?>
<!-- BODY -->
<style>
th:first-child{
  width:80%;
}
</style>

<script>
  function resizeIframe(obj) {
    obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
  }
</script>

<iframe src="tableSummary.php?userID=<?php echo $userID; ?>" style='width:100%; border:none;' scrolling='no' onload='resizeIframe(this)'></iframe>

<!-- /BODY -->
<?php include 'footer.php'; ?>
