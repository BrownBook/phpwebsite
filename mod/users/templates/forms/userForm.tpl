<!-- BEGIN message -->
<span class="smalltext">{MESSAGE}</span>
<hr />
<!-- END message -->
<div class="align-right">{LINKS}</div>
<div class="row">
    <div class="col-md-5">
        {START_FORM}
        <div class="mb-3">
            {AUTHORIZE_LABEL}
            {AUTHORIZE}
            <!-- BEGIN authorize-error -->
            <div class="form-text">{AUTHORIZE_ERROR}</div>
            <!-- END authorize-error -->
        </div>

        <!-- BEGIN username -->
        <div class="mb-3">
            <label for="{USERNAME_ID}" class="form-label">{USERNAME_LABEL_TEXT}</label>
            {USERNAME}
        </div>
        <!-- END username -->

        <!-- BEGIN username-error -->
        <div class="form-text">{USERNAME_ERROR}</div>
        <!-- END username-error -->

        <div class="mb-3">
            <label for="{DISPLAY_NAME_ID}" class="form-label">{DISPLAY_NAME_LABEL_TEXT}</label>
            {DISPLAY_NAME}
        </div>

        <!-- BEGIN password -->
        <div class="mb-3">
            <label for="{PASSWORD1_ID}" class="form-label">{PASSWORD1_LABEL_TEXT}</label>
            {PASSWORD1}&nbsp;{PASSWORD2}
        </div>
        <!-- END password -->

        <!-- BEGIN password-error -->
        <div class="form-text">{PASSWORD_ERROR}</div>
        <!-- END password-error -->

        <!-- BEGIN generate -->
        <div class="mb-3">
            {CREATE_PW} <span id="generated-password"></span>
        </div>
        <!-- END generate -->

        <div class="mb-3">
            <label for="{EMAIL_ID}" class="form-label">{EMAIL_LABEL_TEXT}</label>
            {EMAIL}
        </div>

        <!-- BEGIN email-error -->
        <div class="form-text">{EMAIL_ERROR}</div>
        <!-- END email-error -->

        <!-- BEGIN join-groups -->
        <fieldset>
            <legend>Add to group(s)</legend>
            {JOIN_GROUPS}
            <!-- END join-groups -->
            <div class="text-center">{GO}</div>
        </fieldset>
        {END_FORM}
    </div>
</div>

<!-- BEGIN member-groups -->
<fieldset>
    <legend>Group member</legend>
    {EMPTY_GROUP}
    <ul>
        <!-- BEGIN members -->
        <li>{NAME}</li>
        <!-- END members -->
    </ul>
</fieldset>
<!-- END member-groups -->