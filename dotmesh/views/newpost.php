<?php if (DotMesh_Model_User::isLoggedIn()): ?>
<form name="post" method="post" action="<?=BApp::href('p/')?>">
    <fieldset>
        <textarea id="contents" name="contents"></textarea>
        <label for="is_private">Private?</label><input type="checkbox" name="is_private" id="is_private"/>
        <label for="echo_twitter">Post on Twitter?</label><input type="checkbox" name="echo_twitter" id="echo_twitter"/>
        <button type="submit" class="button" name="do" value="new">Post</button>
    </fieldset>
</form>
<?php endif ?>