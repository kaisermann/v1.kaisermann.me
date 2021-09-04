<div class="contact-form__container">
	<h2><?php lang("contact"); ?></h2>
	<form class="contact-form">
		<div class="form__component animation--1">
			<label for="name"><?php lang("form_name"); ?></label> 
			<input type="text" name="name"/>
		</div>

		<div class="form__component animation--2">
			<label for="email">E-mail</label> 
			<input type="text" name="email" />
		</div>
		
		<div class="form__component animation--3">
			<label for="subject"><?php lang("form_subject"); ?></label> 
			<select name="subject">
				<option value="<?php lang("form_subject_bugdet"); ?>"><?php lang("form_subject_bugdet"); ?></option>
				<option value="<?php lang("form_subject_questions"); ?>"><?php lang("form_subject_questions"); ?></option>
				<option value="<?php lang("form_subject_opinion"); ?>"><?php lang("form_subject_opinion"); ?></option>
				<option value="<?php lang("form_subject_suggestions"); ?>"><?php lang("form_subject_suggestions"); ?></option>
				<option value="<?php lang("form_subject_chat"); ?>"><?php lang("form_subject_chat"); ?></option>
				<option value="<?php lang("form_subject_other"); ?>"><?php lang("form_subject_other"); ?></option>
			</select>
		</div>

		<div class="form__component animation--4">
			<label for="message"><?php lang("form_message"); ?></label>
			<textarea name="message"></textarea>
		</div>

		<div class="form__component form__component--submit animation--5">
			<input type="submit" name="submit" class="form__submit" value="<?php lang("form_send"); ?>"/>
		</div>
	</form>
</div>
