<fieldset style="border:1px solid #090">
        <legend>
        <?php if (isset($_SESSION['user']['role']) && ($_SESSION['user']['role'] == 'admin' || $_SESSION['user']['role'] == 'proger')): ?>
            <?php echo __FILE__ ?>
        <?php endif; ?>

        </legend>
<?php    

if(isset($_REQUEST)){  debug(array('__FILE__' => __FILE__ , '$_REQUEST' => $_REQUEST ));}else{  debug(array('__FILE__' => __FILE__, 'переменная $_REQUEST не определена ' => $_REQUEST  ));}

//bugers([array('REQUEST' => $_REQUEST),array('HTTP_HOST' => $_SERVER['HTTP_HOST'],'__FILE__' => __FILE__,'__CLASS__' => __CLASS__,'__FUNCTION__' => __FUNCTION__,'__LINE__' => __LINE__)],array('autoOpen' => 'REQUEST'));
//if (isset($posts)) {debug(['$compact',$posts]);}
if (isset($hi)) {debug(['$hi',$hi]);}
//                bugers([array('REQUEST' => $_REQUEST),array('HTTP_HOST' => $_SERVER['HTTP_HOST'],'__FILE__' => __FILE__,'__CLASS__' => __CLASS__,'__FUNCTION__' => __FUNCTION__,'__LINE__' => __LINE__)],array('autoOpen' => 'REQUEST'));
$hREQUEST=$_REQUEST;

if(isset($posts)){$_REQUEST=array_merge($_REQUEST, $posts);}else{  debug(array('__FILE__' => __FILE__, 'переменная $posts не определена ' => $_REQUEST  ));}

if (isset($_REQUEST)) {debug($_REQUEST);}
?>
<?php
$hREQUEST=$hREQUEST;
?>
</fieldset>
<!--</fieldset>-->
