<?php
/**
 * Name: Affiliate Code
 * Description: Automagical affiliate things
 */


class LWTV_Affilliates {

	/**
	 * __construct function.
	 */
	function __construct() {
		add_filter( 'widget_text', 'do_shortcode' );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_footer', array( $this, 'apple_auto_link_maker' ) );
		// Disabled Amazon until I fully test it
		// add_action( 'wp_footer', array( $this, 'amazon_publisher_studio' ) );
		add_action( 'wp_head', array( $this, 'add_meta_tags' ), 2 );
	}

	/**
	 * Init
	 */
	public function init() {
		add_shortcode( 'amazon-bounties', array( $this, 'shortcode_amazon_bounties' ) );
		add_shortcode( 'affiliates', array( $this, 'shortcode_affiliates' ) );
	}

	/**
	 * Add Meta Tags for Affiliates
	 */
	public function add_meta_tags() {
		// https://impact.com
		echo '<meta name="ir-site-verification-token" value="1145177634" />';
	}

	/*
	 * Display Affiliate Ads
	 * Usage: [affiliates]
	 * @since 1.0
	*/
	public function shortcode_affiliates( $atts ) {
		if ( is_archive() ) {
			$affiliates = $this->widget_affiliates( 'thin' );
		} else {
			$affiliates = $this->widget_affiliates( 'wide' );
		}

		$thisad = array_rand( $affiliates );

		$advert = '<!-- BEGIN Affiliate Ads --><div class="affiliate-ads ' . sanitize_html_class( $thisad ) . '">' . $affiliates[$thisad] . '</div><!-- END Affiliate Ads -->';

		return $advert;
	}

	/**
	 * Static Affiliates
	 *
	 * This includes links to Yikes!, DreamHost, HTLPodcast, and things that stay pretty static.
	 *
	 * @access public
	 * @return array
	 */
	function widget_affiliates( $type ) {
		
		$affiliates = array(
			'wide' => array( 
				'facetwp'    => '<a href="https://facetwp.com/?ref=91&campaign=LezPress"><img src="' . plugins_url( 'images/facetwp-300x250.png', __FILE__ ) . '"></a>',
				'dreamhost'  => '<a href="https://dreamhost.com/dreampress/"><img src="' . plugins_url( 'images/dreamhost-300x250.png', __FILE__ ) . '"></a>',
				'yikes'      => '<a href="https://www.yikesinc.com"><img src="' . plugins_url( 'images/yikes-300x250.png', __FILE__ ) . '"></a>',
				'htlpodcast' => '<iframe src="//banners.itunes.apple.com/banner.html?partnerId=&aId=1010lMaT&bt=catalog&t=catalog_white&id=1254294886&c=us&l=en-US&w=300&h=250&store=podcast" frameborder=0 style="overflow-x:hidden;overflow-y:hidden;width:300px;height:250px;border:0px"></iframe>',
				'apple'      => '<iframe src="https://widgets.itunes.apple.com/widget.html?c=us&brc=FFFFFF&blc=FFFFFF&trc=FFFFFF&tlc=FFFFFF&d=&t=&m=tvSeason&e=tvSeason&w=250&h=300&ids=&wt=search&partnerId=&affiliate_id=&at=1010lMaT&ct=" frameborder=0 style="overflow-x:hidden;overflow-y:hidden;width:250px;height: 300px;border:0px"></iframe>'
			),
			'thin' => array(
				'amazon1'    => '<iframe src="//rcm-na.amazon-adsystem.com/e/cm?o=1&p=14&l=ur1&category=primeent&banner=0XFKWQVGDFG5VJ2ARBG2&f=ifr&linkID=736cbb4746cfdde557e02035fbef63d5&t=lezpress-20&tracking_id=lezpress-20" width="160" height="600" scrolling="no" border="0" marginwidth="0" style="border:none;" frameborder="0"></iframe>',
				'amazon2'    => '<iframe src="//rcm-na.amazon-adsystem.com/e/cm?o=1&p=29&l=ur1&category=primeent&banner=1QQ0YMZ637C55CZGYWG2&f=ifr&linkID=9acd7e889a1fad94c7bd669757ba1d65&t=lezpress-20&tracking_id=lezpress-20" width="120" height="600" scrolling="no" border="0" marginwidth="0" style="border:none;" frameborder="0"></iframe>',
				'amazon3'    => '<iframe src="//rcm-na.amazon-adsystem.com/e/cm?o=1&p=29&l=ur1&category=primeent&banner=15P2GYM6FRRM04V3BH82&f=ifr&linkID=26d0f2ae73cd170858d9a5be39dd6c9e&t=lezpress-20&tracking_id=lezpress-20" width="120" height="600" scrolling="no" border="0" marginwidth="0" style="border:none;" frameborder="0"></iframe>',
				'amazon4'    => '<iframe src="//rcm-na.amazon-adsystem.com/e/cm?o=1&p=29&l=ur1&category=primeent&banner=18FYEX4M5XZVHBA6HF82&f=ifr&linkID=0f4f7a8109060b84c928d19e4f649855&t=lezpress-20&tracking_id=lezpress-20" width="120" height="600" scrolling="no" border="0" marginwidth="0" style="border:none;" frameborder="0"></iframe>',
				'amazon5'    => '<iframe src="//rcm-na.amazon-adsystem.com/e/cm?o=1&p=29&l=ur1&category=primeent&banner=09PV71N3XGSGHA4MER02&f=ifr&linkID=d459953344c1e054d052faafedb9289f&t=lezpress-20&tracking_id=lezpress-20" width="120" height="600" scrolling="no" border="0" marginwidth="0" style="border:none;" frameborder="0"></iframe>',
				'cbs1'       => '<iframe src="//a.impactradius-go.com/gen-ad-code/1242493/359934/3065/" width="160" height="600" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>',
				'cbs2'       => '<iframe src="//a.impactradius-go.com/gen-ad-code/1242493/359964/3065/" width="160" height="600" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>',
				'cbs3'       => '<iframe src="//a.impactradius-go.com/gen-ad-code/1242493/359962/3065/" width="160" height="600" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>',
				'cbs4'       => '<iframe src="//a.impactradius-go.com/gen-ad-code/1242493/455991/3065/" width="160" height="600" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>',
				'cbs5'       => '<iframe src="//a.impactradius-go.com/gen-ad-code/1242493/379709/3065/" width="160" height="600" scrolling="no" frameborder="0" marginheight="0" marginwidth="0"></iframe>',
			),
		);
		
		return $affiliates[$type];
	}

	/*
	 * Display Amazon Bounties
	 *
	 * THIS IS DEPRECATED!
	 *
	 * @since 1.0
	*/

	public function shortcode_amazon_bounties( $atts ) {
		$ads = '<!-- Deprecated -->';
		return $ads;
	}

	/**
	 * Insert Apple's Auto Link Maker
	 * https://autolinkmaker.itunes.apple.com/?at=1010lMaT
	 */
	public function apple_auto_link_maker() {
		echo "<script type='text/javascript'>var _merchantSettings=_merchantSettings || [];_merchantSettings.push(['AT', '1010lMaT']);(function(){var autolink=document.createElement('script');autolink.type='text/javascript';autolink.async=true; autolink.src= ('https:' == document.location.protocol) ? 'https://autolinkmaker.itunes.apple.com/js/itunes_autolinkmaker.js' : 'http://autolinkmaker.itunes.apple.com/js/itunes_autolinkmaker.js';var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(autolink, s);})();</script>";
	}

	/**
	 * Insert Amazon Publisher Linky Thingy
	 * https://affiliate-program.amazon.com/home/tools/pubstudio
	 */
	public function amazon_publisher_studio() {
		echo '<!-- Amazon Publisher Studio --><script> var amzn_ps_tracking_id = "lezpress-20"; </script><script async="true" type="text/javascript" src="//ps-us.amazon-adsystem.com/scripts/US/studio.js"></script>';
	}


	/**
	 * Determine what to call for actors
	 * Pick a random ad just for fun
	 */
	public function actors( $id, $type ) {
		$number = rand();

		if ($number % 2 == 0) {
			$return = self::amazon( $id, $type );
		} else {
			$return = self::cbs( $id, $type );
		}

		return $return;
	}

	/**
	 * Determine what to call for characters
	 */	
	public function characters( $id, $type ) {
		$number = rand();

		if ($number % 2 == 0) {
			$return = self::amazon( $id, $type );
		} else {
			$return = self::cbs( $id, $type );
		}

		return $return;
	}

	/**
	 * Determine what to call for shows
	 * This is much more complex!
	 */
	public function shows( $id, $type ) {

		// Default
		$return = self::amazon( $id, $type );
		
		if ( $type == 'affiliate' ) {
			$return = self::affiliate_link( $id );
		} else {
			// Get the slug (needed for Star Trek
			$slug = get_post_field( 'post_name', $id );

			// If Vimeo:
			if ( has_term( 'vimeo', 'lez_stations', $id ) ) {
				// Uncomment and fix when Vimeo approves us
				// $return = self::vimeo( $id, $type );
			}

			// If CBS (or Star Trek)
			if ( has_term( 'cbs', 'lez_stations', $id ) || has_term( 'cbs-all-access', 'lez_stations', $id ) || strpos( $slug, 'star-trek' ) !== false ) {
				$return = self::cbs( $id, $type );
			}
		}
		return $return;
	}

	/**
	 * Call Amazon Affilate Data
	 */
	function amazon( $id, $type ) {
		include_once( 'amazon.php' );
		return LWTV_Affiliate_Amazon::show_ads( $id, $type );
	}

	/**
	 * Call CBS Affilate Data
	 */
	function cbs( $id, $type ) {
		include_once( 'cbs.php' );
		return LWTV_Affiliate_CBS::show_ads( $id, $type );
	}

	/**
	 * Call Vimeo Affiliate Data
	 */
	function vimeo( $id, $type ) {
		include_once( 'vimeo.php' );
		return LWTV_Affiliate_Vimeo::show_ads( $id, $type );
	}

	/**
	 * Call Custom Affiliate Links
	 * This is used by shows to figure out where people can watch things
	 */
	function affiliate_link( $id ) {

		$aff_type = get_post_meta( $id, 'lezshows_affiliate', true );
		$aff_url  = get_post_meta( $id, 'lezshows_affiliateurl', true );

		// If the type is a URL but URL is empty, bail
		if ( $aff_type == 'url' && empty( $aff_url ) ) return;

		$data = array( 'link' => '', 'text' => 'Watch online now', 'img'  => '', );

		switch( $aff_type ) {
			case "amazon":
				$data = self::amazon( $id, 'text' );
				break;
			case "apple":
				$data['link'] = 'https://www.apple.com/itunes/charts/tv-shows/';
				$data['text'] = 'Watch TV on iTunes';
				break;
			case "cbs":
				$data = self::cbs( $id, 'text' );
				break;
			case "vimeo":
				$data['link'] = 'https://join.vimeo.com';
				$data['text'] = 'Watch on Vimeo - Buy Directly from Creators';
				break;
			case "youtube":
				$data['link'] = 'https://www.youtube.com/red';
				$data['text'] = 'Watch Ad Free on YouTube Red';
				break;
			case "url":
				$data['link'] = $aff_url;
				break;
		}

		$icon   = lwtv_yikes_symbolicons( 'tv-hd.svg', 'fa-tv' );
		$output = '<a target="_blank" href="' . esc_url( $data['link'] ) . '"><button type="button" class="btn btn-info btn-lg btn-block">' . $icon . $data['text'] . $data['img'] . '</button></a>';

		return $output;

	}

}
new LWTV_Affilliates();