<?php
// Calling conventions
// $q - <CustomQueue> object for this navigation entry
// $children - <Array<CustomQueue>> all direct children of this queue
$queue = $q;
$hasChildren = count($children) > 0;
$selected = $_REQUEST['queue'] == $q->getId();
global $thisstaff;
?>
<!-- SubQ class: only if top level Q has subQ -->
<li <?php if ($hasChildren)  echo 'class="subQ"'; ?>>

  <span class="<?php if ($thisstaff->isAdmin() || !$q->isAQueue())  echo 'personalQmenu'; ?>
    pull-right newItemQ queue-count"
    data-queue-id="<?php echo $q->id; ?>"><span class="faded-more">-</span>
  </span>

  <a class="truncate <?php if ($selected) echo ' active'; ?>" href="<?php echo $queue->getHref();
    ?>" title="<?php echo Format::htmlchars($q->getName()); ?>">
      <?php
        if ($q->isTeamVisible()) { ?><i class="icon-group"></i> <?php }
        echo Format::htmlchars($q->getName()); ?>
      <?php
        if ($hasChildren) { ?>
            <i class="icon-caret-right"></i>
      <?php } ?>
    </a>

    <?php
    $closure_include = function($q, $children) {
        global $thisstaff, $ost, $cfg;
        include __FILE__;
    };
    if ($hasChildren) { ?>
    <ul class="subMenuQ">
    <?php
    foreach ($children as $_) {
        list($q, $childs) = $_;
        if (!$q->isPrivate())
          $closure_include($q, $childs);
    }

    // Include personal sub-queues
    $first_child = true;
    foreach ($children as $_) {
      list($q, $childs) = $_;
      if ($q->isPrivate()) {
        if ($first_child) {
          $first_child = false;
          echo '<li class="personalQ"></li>';
        }
        $closure_include($q, $childs);
      }
    } ?>
    </ul>
<?php
} ?>
</li>
