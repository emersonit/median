<?php
if (!isset($page_uuid)) {
    $page_uuid = 'default-page';
}
?>

<div class="row footer">
    <div class="column full">
        <p>Median &copy; 2007-<?php echo date('Y'); ?> <a href="http://it.emerson.edu/">Emerson College IT</a></p>
    </div>
</div>

</div>

<script type="text/javascript" src="https://pages.emerson.edu/cdn/js/jquery-2.1.3.min.js"></script>
<script type="text/javascript" src="https://pages.emerson.edu/cdn/js/jquery-ui-1.11.3.min.js"></script>
<script type="text/javascript" src="/js/jquery.iframe-transport.js"></script>
<script type="text/javascript" src="/js/jquery.sticky.js"></script>
<?php
if ($page_uuid == 'player-page') {
?>
<script type="text/javascript" src="/js/jquery.flot.min.js"></script>
<script type="text/javascript" src="/js/jquery.flot.time.min.js"></script>
<?php
} // end if player page check
?>
<script type="text/javascript" src="/js/median.js"></script>
</body>
</html>
