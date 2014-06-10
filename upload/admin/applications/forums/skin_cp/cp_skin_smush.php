<?php

class cp_skin_smush extends output {
	public function main() {
		$IPBHTML = "";
		$IPBHTML .= <<<HTML
		<div class="acp-box">
			<table class="ipsTable">
				<tr>
					<td style="width: 50%; text-align:center;">
						{$this->lang->words['smush_smushabout']}
						<br />
						{$this->lang->words['smush_information']}
						<a type="submit" class="button primary" href="{$this->settings['base_url']}{$this->form_code}do=smushall">{$this->lang->words['smush_smushall']}</a>
					</td>
					<td style="width: 50%; text-align:center; background:#fefefe;">
						{$this->lang->words['smush_smushed']}
						<br /><br />
						<b>Also you can save we with your donate to <a href="http://skinod.com">Skinod</a> group</b>
						<br />
						<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=Y7H3SGH56C9CG">
							<img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif">
						</a>
					</td>
				</tr>
			</table>
		</div>
HTML;
		
		return $IPBHTML;
	}
}