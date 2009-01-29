<div id="profiel">
	<div id="profielregel">
		<div class="naam">
			<div class="floatR">
				{$profhtml.uid|pasfoto}<br />
				<div class="knopjes">
					{if $magBewerken}
						<a href="/communicatie/profiel/{$profhtml.uid}/edit" class="knop"><img src="{$csr_pics}forum/bewerken.png" title="Bewerk groep" />Bewerken</a><br />
					{/if}
					{if $isAdmin}
						<a href="/tools/stats.php?uid={$profhtml.uid}" class="knop">Overzicht van bezoeken</a><br />
						<a href="/communicatie/profiel/{$profhtml.uid}/wachtwoord" class="knop" onclick="return confirm('Weet u zeker dat u het wachtwoord van deze gebruiker wilt resetten?')">Reset wachtwoord</a><br />
					{/if}
				</div>
			</div>
			{if $melding!=''}{$melding}<br />{/if}
			<h1>{$profhtml.fullname}</h1>
		</div>
	</div>
	
	<div class="profielregel">
		<div class="left">Naam</div>
		<div class="gegevens">
			<div class="label">&nbsp;</div>{$profhtml.civitasnaam}<br />
			<div class="label">Lidnummer:</div> {$profhtml.uid} <br />
			<div class="label">Bijnaam:</div> {$profhtml.nickname} <br />
			{if $profhtml.gebdatum!='0000-00-00'}<div class="label">Geb.datum:</div> {$profhtml.gebdatum|date_format:"%d-%m-%Y"}{/if}
		</div>
	</div>
	<div class="profielregel">
		<div class="left">Adres</div>	
		<div class="gegevens">
			<div class="gegevenszelf">
				<div class="label">
					{if $profhtml.adres!=''}
						<a href="http://maps.google.nl/maps?daddr={$profhtml.adres|urlencode}+{$profhtml.woonplaats|urlencode}+{$profhtml.land|urlencode}">
							<img src="{$csr_pics}layout/googlemaps.gif" width="35px" alt="googlemap voor dit adres" />
						</a>
					{/if}
				</div>
				<div class="adres">
					{$profhtml.woonoord}
					{$profhtml.adres}<br />
					{$profhtml.postcode} {$profhtml.woonplaats}<br />
					{$profhtml.land}<br />
					{if $profhtml.telefoon!=''}{$profhtml.telefoon}<br />{/if}
					{if $profhtml.mobiel!=''}{$profhtml.mobiel}<br />{/if}
				</div>
			</div>
			{if $isOudlid===false}
			<div class="gegevensouders">			
				{if $profhtml.o_adres!=''}
					<div class="label">
						<a href="http://maps.google.nl/maps?daddr={$profhtml.o_adres|urlencode}+{$profhtml.o_woonplaats|urlencode}+{$profhtml.o_land|urlencode}">
							<img src="{$csr_pics}layout/googlemaps.gif" width="35px" alt="googlemap voor dit adres" />
						</a>
					</div>
				{/if}
				<div class="adres">
					{if $profhtml.o_adres!=''}
						<strong>Ouders:</strong><br />
						{$profhtml.o_adres}<br />
						{$profhtml.o_postcode} {$profhtml.o_woonplaats}<br />
						{$profhtml.o_land}<br />
						{$profhtml.o_telefoon}
					{/if}
				</div>
			</div>
			{/if}
			<div style="clear: left;"></div>
		</div>
	</div>
	<div class="profielregel">
		<div class="left">Contact</div>	
		<div class="gegevens">
			<div class="label">Email:</div><a href="mailto:{$profhtml.email}">{$profhtml.email}</a><br />	
			{if $profhtml.icq!=''}<div class="label">ICQ:</div> {$profhtml.icq}<br />{/if}
			{if $profhtml.msn!=''}<div class="label">MSN:</div> {$profhtml.msn}<br />{/if}
			{if $profhtml.jid!=''}<div class="label">Jabber:</div> {$profhtml.jid}<br />{/if}
			{if $profhtml.skype!=''}<div class="label">Skype:</div> {$profhtml.skype}<br />{/if}
			{if $profhtml.website!=''}<div class="label">Website:</div> <a href="{$profhtml.website}" class="linkExt">{$profhtml.website|truncate:30}</a><br />{/if}
		</div>	
	</div>
	<div class="profielregel">
		<div class="left">Civitas</div>	
		<div class="gegevens">
			<div class="label">Studie:</div> {$profhtml.studie}<br />
			<div class="label">Studie sinds:</div> {$profhtml.studiejaar}<br />
			<div class="label">Lid sinds:</div> {$profhtml.lidjaar}<br />
			<br />
			{if $isOudlid===false}
				<div class="label">Kring:</div> <a href="/communicatie/moten#kring{$profhtml.moot}.{$profhtml.kring}">{$profhtml.moot}.{$profhtml.kring}</a><br />
			{/if}
		</div>
	</div>
	<div class="profielregel">
		<div class="left">Groepen</div>	
		<div class="gegevens">		
			{$profhtml.groepen}
			<div style="clear: left;"></div>
		</div>
	</div>
	{if $profhtml.saldografiek!='' OR $profhtml.bankrekening!=''}
		<div class="profielregel">
			<div class="left">Financi&euml;el</div>	
			<div class="gegevens">		
				{if $profhtml.bankrekening!=''}<div class="label">Bankrekening:</div> {$profhtml.bankrekening}<br />{/if}
				{if $profhtml.saldi!=''}
					<br />
					{foreach from=$profhtml.saldi item=saldo}
						{if $saldo.saldo!=0}
							<div class="label">{$saldo.naam}saldo:</div> 
								<div {if $saldo.saldo < 0} style="color: red;"{/if}>&euro; {$saldo.saldo|number_format:2:",":"."}</div>
						{/if}
					{/foreach}
				{/if}
				{$profhtml.saldografiek}
			</div>
		</div>
	{/if}
	
	
	<div class="profielregel">
		<div class="left">Maaltijden</div>	
		<div class="gegevens">
			{if $profhtml.eetwens!=''}
				<div class="label">Dieet:</div>
				<div class="data">{$profhtml.eetwens}</div>
				<br />
			{/if}
			{if $profhtml.abos|@count > 0}
				<div class="label">
					Abo's:
					{if $lid->getUid()==$profhtml.uid}
						<br /><a href="/actueel/maaltijden/voorkeuren.php" class="knop" title="Abo's bewerken"><img src="{$csr_pics}forum/bewerken.png" title="Bewerk groep" /></a>
					{/if}
				</div>
				<ul class="nobullets data">
				{foreach from=$profhtml.abos item=abo}
					<li>{$abo}</li>
				{/foreach}
				</ul>
				<br />
			{/if}
			{if $lid->getUid()==$profhtml.uid OR $lid->hasPermission('P_MAAL_MOD')}
				<div class="label">Recent:</div>
				<ul class="nobullets data">
					{foreach from=$profhtml.recenteMaaltijden item=maaltijd}
						<li><em>{$maaltijd.datum|date_format:"%a %d-%m"}</em> - {$maaltijd.tekst}</li>
					{/foreach}
				</ul>
			{/if}
		</div>
	</div>
	
</div>
