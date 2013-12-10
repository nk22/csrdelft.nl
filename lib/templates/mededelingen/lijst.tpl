<div class="mededelingen-overzichtlijst">
	{foreach from=$lijst key=groepering item=mededelingen}
		<div class="mededelingenlijst-block">
			<div class="mededelingenlijst-block-titel">{$groepering|ucfirst}</div>
				{foreach from=$mededelingen item=mededeling}
					<div {if $mededeling->getId()==$geselecteerdeMededeling->getId()}id="actief" {/if}class="mededelingenlijst-item{if $mededeling->isVerborgen()} verborgen-item{/if}{if $mededeling->isVerwijderd()} verwijderd-item{/if}">
						{if $mededeling->getCategorie()->getPlaatje() !=''}
							<div class="mededelingenlijst-plaatje">
								<a href="{$pagina_root}{$mededeling->getId()}">
									<img src="{$csr_pics}nieuws/{$mededeling->getCategorie()->getPlaatje()}" width="10px" height="10px" />
								</a>
							</div>
						{/if}
					<div class="itemtitel">
						{* {$mededeling->getDatum()} *}
						<a href="{$pagina_root}{$mededeling->getId()}"{if $mededeling->isModerator()} style="{if !$mededeling->isPrive()}font-style: italic;{/if}{if $mededeling->getZichtbaarheid()=='wacht_goedkeuring'}font-weight: bold;{/if}"{/if}>{$mededeling->getAfgeknipteTitel()}</a>
					</div>
			</div>
				{/foreach}
		</div> {* Einde mededelingenlijst-block*}
	{/foreach}
		<div class="mededelingen_paginering">
		Pagina: {sliding_pager baseurl="`$pagina_root`pagina/" 
					pagecount=$totaalAantalPaginas curpage=$huidigePagina
					txt_prev="&lt;" separator="" txt_next="&gt;" show_always=true show_first_last=false show_prev_next=false}
		</div>
	
</div> {* Einde mededelingen-overzichtlijst *}