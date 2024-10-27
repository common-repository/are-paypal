{include file='last_action.inc.tpl'}
<div class="wrap">
	<h2>{$PageHeader}</h2>
	<p></p>
	<form action="" method="post" id="{$Prefix}-conf">
	
		{literal}
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#{/literal}{$TabDivID}{literal}").tabs({
					cookie: {
						// store cookie for a day, without, it would be a session cookie
						//expires: 1
					}
				}
				);
				});
			</script>
		{/literal}
		<div id="wrapwrap">
			<div id="{$TabDivID}">
		    <ul>
		    	<li><a href="#fragment-0"><span>{$BasicSettingsTabText}</span></a></li>
		        <li><a href="#fragment-1"><span>{$MessagesTabText}</span></a></li>
		        <li><a href="#fragment-2"><span>{$TemplatesTabText}</span></a></li>
		        <li><a href="#fragment-3"><span>{$PurchasedItemLibraryTabText}</span></a></li>
		        <li><a href="#fragment-4"><span>{$PayToRegisterTabText}</span></a></li>
		        <li><a href="#fragment-5"><span>{$SmartyTabText}</span></a></li>
		        <li><a href="#fragment-6"><span>{$BonusPostSetupTabText}</span></a></li>
		    </ul>
		    <div id="fragment-0">
				<p>
					<input id="test" name="test" type="checkbox" value="checked" {if $PayPalSandBoxMode}checked='checked' {/if}/>
					&nbsp;&nbsp;
					<label for="test">
						<a href="http://developer.paypal.com" target="_blank">{$UsePayPalSandBoxLinkText}</a> {$UsePayPalSandBoxNextToLinkText}
					</label>
				</p>
				<p>
					<input id="Suppress_Notification_Emails" name="Suppress_Notification_Emails" type="checkbox" value="checked" {if $Suppress_Notification_EmailsMode} checked='checked' {/if} />
					&nbsp;&nbsp;
					<label for="Suppress_Notification_Emails">
						{$Suppress_Notification_EmailsLabel}
					</label>
				</p>
				<p>
					<input id="Suppress_MonetizedBy_Link" name="Suppress_MonetizedBy_Link" type="checkbox" value="checked" {if $Suppress_MonetizedBy_LinkMode} checked='checked' {/if} />
					&nbsp;&nbsp;
					<label for="Suppress_MonetizedBy_Link">
						{$Suppress_MonetizedBy_LinkLabel}
					</label>
				</p>
				<h3>
					<label for="PayPal_Email">
						{$PayPal_EmailLabel}
					</label>
				</h3>
				<p>
					<input value="{$PayPal_Email}" id="PayPal_Email" name="PayPal_Email" type="text" size="60" class="ArePayPalSettingsTextBox"/>
				</p>
		    </div>
		    <div id="fragment-1">
		    	<h2>{$TextsToShowInPayForContent}</h2>
				<h3>
					<label for="TextToShowIfNotLogedIn">
						{$TextToShowIfNotLogedInLabel}
					</label>
				</h3>
				<p>
					<textarea rows="" cols="" id="TextToShowIfNotLogedIn" name="TextToShowIfNotLogedIn" class="ArePayPalSettingTextArea">{$TextToShowIfNotLogedIn}</textarea> 
				</p>
				<h3>
					<label for="TextToShowIfNotPurchased">
						{$TextToShowIfNotPurchasedLabel}
					</label>
				</h3>
				<p>
					<textarea rows="" cols="" id="TextToShowIfNotPurchased" name="TextToShowIfNotPurchased" class="ArePayPalSettingTextArea">{$TextToShowIfNotPurchased}</textarea> 
				</p>
				<h2>{$TextsToShowInPayToRegister}</h2>
				<h3>
					<label for="LoginMessages_EmptyUserName">
						{$LoginMessages_EmptyUserNameLabel}
					</label>
				</h3>
				<p>
					<textarea rows="" cols="" id="LoginMessages_EmptyUserName" name="LoginMessages_EmptyUserName" class="ArePayPalSettingTextArea">{$LoginMessages_EmptyUserName}</textarea> 
				</p>
		
				<h3>
					<label for="LoginMessages_EmptyPassword">
						{$LoginMessages_EmptyPasswordLabel}
					</label>
				</h3>
				<p>
					<textarea rows="" cols="" id="LoginMessages_EmptyPassword" name="LoginMessages_EmptyPassword" class="ArePayPalSettingTextArea">{$LoginMessages_EmptyPassword}</textarea> 
				</p>
		
				<h3>
					<label for="LoginMessages_InvalidUserName">
						{$LoginMessages_InvalidUserNameLabel}
					</label>
				</h3>
				<p>
					<textarea rows="" cols="" id="LoginMessages_InvalidUserName" name="LoginMessages_InvalidUserName" class="ArePayPalSettingTextArea">{$LoginMessages_InvalidUserName}</textarea> 
				</p>
				<h3>
					<label for="LoginMessages_IncorrectPassword">
						{$LoginMessages_IncorrectPasswordLabel}
					</label>
				</h3>
				<p>
					<textarea rows="" cols="" id="LoginMessages_IncorrectPassword" name="LoginMessages_IncorrectPassword" class="ArePayPalSettingTextArea">{$LoginMessages_IncorrectPassword}</textarea> 
				</p>
				<h3>
					<label for="LoginMessages_PayToRegisterAuthFailed">
						{$LoginMessages_PayToRegisterAuthFailedLabel}
					</label>
				</h3>
				<p>
					<textarea rows="" cols="" id="LoginMessages_PayToRegisterAuthFailed" name="LoginMessages_PayToRegisterAuthFailed" class="ArePayPalSettingTextArea">{$LoginMessages_PayToRegisterAuthFailed}</textarea> 
				</p>
				<p class="submit">
					<input type="submit" name="restoreloginmessagedefaults" value="{$RestoreLoginMessageDefaults}" />
				</p>
		
		    </div>
		    <div id="fragment-2">
				<h3>
					<label for="InstantPaymentTemplate">
						{$InstantPaymentTemplateLabel}
					</label>
				</h3>
				<p>
					<textarea rows="" cols="" id="InstantPaymentTemplate" name="InstantPaymentTemplate" class="ArePayPalSettingTextArea">{$InstantPaymentTemplate}</textarea>
				</p>
				{$TemplateExamples.InstantPayment}
				<h3>
					<label for="RecurentPaymentTemplate">
						{$RecurentPaymentTemplateLabel}
					</label>
				</h3>
				<p>
					<textarea rows="" cols="" id="RecurentPaymentTemplate" name="RecurentPaymentTemplate" class="ArePayPalSettingTextArea">{$RecurentPaymentTemplate}</textarea>
				</p>
				{$TemplateExamples.RecurentPayment}
				<h3>
					<label for="LoginButtonTemplate">
						{$LoginButtonTemplateLabel}
					</label>
				</h3>
				<p>
					<textarea rows="" cols="" id="LoginButtonTemplate" name="LoginButtonTemplate" class="ArePayPalSettingTextArea">{$LoginButtonTemplate}</textarea>
				</p>
				{$TemplateExamples.LoginButton}
				<p class="submit">
					<input type="submit" name="restoretemplatedefaults" value="{$RestoreDefaults}" />
				</p>
		    </div>
		    <div id="fragment-3">
				<h3>
					<label for="users_library_page">
						{$users_library_pageLabel}
					</label>
				</h3>
				<p>
					<select id="users_library_page" name="users_library_page">
						<option value="" {if $Users_Library_Page} {else} selected='selected' {/if}>---</option>
						{foreach from=$Posts item=post}
							<option 
								{if $Users_Library_Page == $post->post_title} selected='selected' {/if} 
								value="{$post->post_title}">
								{$post->post_title}
							</option>				
						{/foreach}
					</select>
				</p>
		    </div>
		    <div id="fragment-4">
				<h3>
					<label for="paytoregister_page">
						{$paytoregister_pageLabel}
					</label>
				</h3>
				<p>
					<select id="paytoregister_page" name="paytoregister_page">
						<option value="" {if $Users_PayToRegister_Page} {else} selected='selected' {/if}>---</option>
						{foreach from=$Posts item=post}
							<option 
								{if $Users_PayToRegister_Page == $post->ID} selected='selected' {/if} 
								value="{$post->ID}">
								{$post->post_title}
							</option>				
						{/foreach}
					</select>
				</p>
		    </div>
		    <div id="fragment-5">
				<h3>
					<label for="SmartyCacheDirectory">
						{$SmartyCacheDirectoryLabel}
					</label>
				</h3>
				<p>
					<input value="{$SmartyCacheDirectory}" id="SmartyCacheDirectory" name="SmartyCacheDirectory" type="text" size="120" class="ArePayPalSettingsTextBox"/>
				</p>
				<p>
				Plugin will automatically reset the folder to default if you make a mistake here.
				</p>
		    </div>
		    <div id="fragment-6">
				<p>
					<input id="BonusPostManualSetup" name="BonusPostManualSetup" type="checkbox" value="checked" {if $BonusPostManualSetup} checked='checked' {/if} />
					<label for="BonusPostManualSetup">
						{$BonusPostManualSetupLabel}
					</label>
					<p style="font-size:10px;">{$BonusPostManualSetupDescription}</p>
				</p>
		    </div>
			</div>
			<p class="submit">
				<input type="submit" name="submit" value="{$UpdateOptions}" />
			</p>
		</div>
	</form>
</div>
