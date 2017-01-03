<?php

    script('hookextract', 'script');
    style('hookextract', 'style');
    
    
?>

<div id="app">
    <div id="app-navigation">
        <?php print_unescaped($this->inc('part.navigation')); ?>
        <?php print_unescaped($this->inc('part.settings')); ?>
    </div>

    <div id="app-content">
        <img src="<?php p($_['imgurl']) ?>" />
        <div id="app-content-wrapper">
            <?php print_unescaped($this->inc('part.content')); ?>
            <?php if ($_['upload'] > 0) { 
                echo '<div id="afterupl">'
                . '<p>'.$_['upload'].' entries were successfully uploaded</p>'
                        . '</div>';
                //echo "<script type='text/javascript'>alert('submitted successfully!')</script>";
            } elseif ($_['upload'] == -1) {
                echo '<div id="afterupl">'
                . "<p>ERROR: No data was uploaded</p>"
                        . '</div>';
            } ?>

        </div>
    </div>
</div>
