<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

$qs = array();

$orgs = Organization::objects()
    ->annotate(array('user_count'=>SqlAggregate::COUNT('users')));

if (@$_REQUEST['query']) {
    $search = $_REQUEST['query'];
    $orgs->filter(Q::any(array(
        'name__contains' => $search,
        // Add search for cdata — TODO: Use search index
        'cdata_entry__answers__value__contains' => $search,
    )));
    $qs += array('query' => $_REQUEST['query']);
}

$sortOptions = array('name' => 'name',
                     'users' => 'user_count',
                     'create' => 'created',
                     'update' => 'updated');
$orderWays = array('DESC'=>'-','ASC'=>'');
$sort= ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'name';
//Sorting options...
if ($sort && $sortOptions[$sort])
    $order_column =$sortOptions[$sort];

$order_column = $order_column ?: 'name';

if ($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order = $orderWays[strtoupper($_REQUEST['order'])];

$order = $order ?: '';
if ($order_column && strpos($order_column,','))
    $order_column = str_replace(','," $order,",$order_column);

$x=$sort.'_sort';
$$x=' class="'.($order == '' ? 'asc' : 'desc').'" ';

$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate(count($orgs), $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('orgs.php', $qs);
$pageNav->paginate($orgs);
$qstr.='&amp;order='.($order=='-' ? 'ASC' : 'DESC');

$orgs->order_by($order . $order_column);

$_SESSION[':Q:orgs'] = $orgs;
?>
<h2><?php echo __('Organizations'); ?></h2>
<div class="pull-left" style="width:700px;">
    <form action="orgs.php" method="get">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="search">
        <table>
            <tr>
                <td><input type="text" id="basic-org-search" name="query" size=30 value="<?php echo Format::htmlchars($_REQUEST['query']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off"></td>
                <td><input type="submit" name="basic_search" class="button" value="<?php echo __('Search'); ?>"></td>
                <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
            </tr>
        </table>
    </form>
 </div>

<div class="pull-right">
<?php if ($thisstaff->getRole()->hasPerm(User::PERM_CREATE)) { ?>
    <a class="action-button add-org"
        href="#">
        <i class="icon-plus-sign"></i>
        <?php echo __('Add Organization'); ?>
    </a>
<?php } ?>
    <span class="action-button" data-dropdown="#action-dropdown-more">
        <i class="icon-caret-down pull-right"></i>
        <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
    </span>
    <div id="action-dropdown-more" class="action-dropdown anchor-right">
        <ul>
<?php if ($thisstaff->getRole()->hasPerm(Organization::PERM_DELETE)) { ?>
            <li><a class="orgs-action" href="#delete">
                <i class="icon-trash icon-fixed-width"></i>
                <?php echo __('Delete'); ?></a></li>
<?php } ?>
        </ul>
    </div>
</div>

<div class="clear"></div>
<?php
$showing = $search ? __('Search Results').': ' : '';
if (count($orgs))
    $showing .= $pageNav->showing();
else
    $showing .= __('No organizations found!');
?>
<form id="orgs-list" action="orgs.php" method="POST" name="staff" >
 <?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" id="action" name="do" value="" >
 <input type="hidden" id="selected-count" name="count" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th nowrap width="12"> </th>
            <th width="400"><a <?php echo $name_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name'); ?></a></th>
            <th width="100"><a <?php echo $users_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=users"><?php echo __('Users'); ?></a></th>
            <th width="150"><a <?php echo $create_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=create"><?php echo __('Created'); ?></a></th>
            <th width="145"><a <?php echo $update_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=update"><?php echo __('Last Updated'); ?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        if (count($orgs)) {
            $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
            foreach ($orgs as $row) {

                $sel=false;
                if($ids && in_array($row->id, $ids))
                    $sel=true;
                ?>
               <tr id="<?php echo $row->id; ?>">
                <td nowrap>
                    <input type="checkbox" value="<?php echo $row->id; ?>" class="mass nowarn"/>
                </td>
                <td>&nbsp; <a href="orgs.php?id=<?php echo $row->id; ?>"><?php echo $row->name; ?></a> </td>
                <td>&nbsp;<?php echo $row->user_count; ?></td>
                <td><?php echo Format::date($row->created); ?></td>
                <td><?php echo Format::datetime($row->updated); ?>&nbsp;</td>
               </tr>
            <?php
            } //end of foreach
        } ?>
    </tbody>
</table>
<?php
if (count($orgs)) {
    echo sprintf('<div>&nbsp;%s: %s &nbsp; <a class="no-pjax"
            href="orgs.php?a=export&qh=%s">%s</a></div>',
            __('Page'),
            $pageNav->getPageLinks(),
            $qhash,
            __('Export'));
}
?>
</form>

<script type="text/javascript">
$(function() {
    $('input#basic-org-search').typeahead({
        source: function (typeahead, query) {
            $.ajax({
                url: "ajax.php/orgs/search?q="+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            window.location.href = 'orgs.php?id='+obj.id;
        },
        property: "/bin/true"
    });

    $(document).on('click', 'a.add-org', function(e) {
        e.preventDefault();
        $.orgLookup('ajax.php/orgs/add', function (org) {
            window.location.href = 'orgs.php?id='+org.id;
         });

        return false;
     });

    var goBaby = function(action) {
        var ids = [],
            $form = $('form#orgs-list');
        $(':checkbox.mass:checked', $form).each(function() {
            ids.push($(this).val());
        });
        if (ids.length && confirm(__('You sure?'))) {
            $form.find('#action').val(action);
            $.each(ids, function() { $form.append($('<input type="hidden" name="ids[]">').val(this)); });
            $form.find('#selected-count').val(ids.length);
            $form.submit();
        }
        else if (!ids.length) {
            $.sysAlert(__('Oops'),
                __('You need to select at least one item'));
        }
    };
    $(document).on('click', 'a.orgs-action', function(e) {
        e.preventDefault();
        goBaby($(this).attr('href').substr(1));
        return false;
    });
});
</script>
