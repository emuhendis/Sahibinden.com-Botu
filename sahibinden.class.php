<?php

class Sahibinden{
    static $ana_url = 'https://www.sahibinden.com';
    static $data = array();
    static function Kategori($url = NULL){
        self::$data = [];
        if($url != NULL) {
            //20.09.2017 alt-kategorilerin olduğu  linklerin alınması
            $html = self::Curl( self::$ana_url.'/alt-kategori/'.$url);
            $pattern = '/<div>[\r\n ]{2,}<a href="(.*?)">(.*?)<\/a>[\r\n ]{2,}<span>\((.*?)\)<\/span>/';
            preg_match_all( $pattern, $html, $result, PREG_SET_ORDER, 0);
            
            foreach($result as $key => $val){
                self::$data[ ] = array (
                    'title' => $val[2],
                    'uri' => $val[1] ,
                    'url' => self::$ana_url . $val[1],
					'count' => $val[3]
               );
            }

        }else{
            
            //20.09.2017 ana kategorilerin olduğu  linklerin alınması
            $html = self::Curl(self::$ana_url);

            $pattern = '/<a href=\"\/kategori([^\"]*)\">[\r\n ]{2,}([^\"]*)<\/a>[\r\n ]{2,}\<span\>[\r\n ]{2,}\(([^\"]*)\)/m';
            preg_match_all( $pattern, $html, $result, PREG_SET_ORDER, 0);

            foreach($result as $key => $val){
                self::$data[ ] = array (
                    'title' => $val[2],
                    'uri' => $val[1] ,
                    'url' => self::$ana_url . $val[1],
					'count' => $val[3]
               );
            }
        }
        return self::$data;
    }
    static function Liste($kategoriLink, $sayfa = '0'){
        //21.09.2017 listedeki ilanların bilgilerinin alınması
        $items = array();
        $page = '?pagingOffset=' . $sayfa;
        $html = self::Curl( self::$ana_url . '/' . $kategoriLink . $page);
        $pattern = '/<tr data-id="\d*" class="searchResultsItem.*?">.*?<\/tr>/';
        preg_match_all( $pattern, $html, $result, PREG_SET_ORDER, 0);
        foreach($result as $detay){
            preg_match( '/<img src="(.*?)" alt=".*#(\d*)" title="(.*?)"\/>/', $detay[0], $image);
            preg_match( '/<a class="classifiedTitle" href="(.*?)">(.*?)<\/a>/', $detay[0], $title);
            $items[] = array(
                'id' =>  $image[2],
                'image' => $image[1],
                'title' => self::replaceSpace($image[3] ? $image[3] : trim($title[2] )),
                'url' => self::$ana_url . $title[1]
			);
        }
        return $items;
    }
    static function Detay($url = NULL){
        if ($url != NULL ) {
            $open = self::Curl($url);
            // genel özellikler
            preg_match_all( '/<ul class="classifiedInfoList">(.*?)<\/ul>/', $open, $propertie);
            $prop = self::replaceSpace($propertie[1][0]);
            preg_match_all( '/<li> <strong>(.*?)<\/strong>(.*?)<span(.*?)>(.*?)<\/span> <\/li>/', $prop, $p);
            foreach($p[1] as $index => $val){
                $properties[ trim($val ) ] = str_replace( '&nbsp;', '', trim($p[4][ $index ] ));
            }
            // price
            preg_match('/<div class="classifiedInfo ">(.*?)<\/div>/', $open, $extra);
            $extras = self::replaceSpace($extra[1]);
            preg_match('/<h3>(.*?)<\/h3>/', $extras, $price);
			preg_match('/<a (.*?)>(.*?)<\/a>/', $extras, $price_link);
			$price = str_replace($price_link[0],"",$price[1]);
            $price = trim($price);
			
			// address
			preg_match('/<div class="classifiedInfo ">(.*?)<\/div>/', $open, $addrs);	
			$addrs2 = self::replaceSpace($addrs[1]);
            preg_match_all('/<h2>(.*?)<\/h2>/', $addrs2, $addrs3);
			preg_match_all('/<a href="(.*?)">(.*?)<\/a>/', $addrs3[1][0], $addrs4);
			$address = array(
                'il' => trim($addrs4[2][0]),
                'ilce' => trim($addrs4[2][1]),
                'mahalle' => trim($addrs4[2][2])
           );
            // username
            preg_match('/<h5>(.*?)<\/h5>/', $open, $username);
            $username = $username[1];
            // contact info
            preg_match('/<ul class="userContactInfo">(.*?)<\/ul>/', $open, $contact_info);
            $contact_info = self::replaceSpace($contact_info[1]);
            preg_match_all('/<li> <strong>(.*?)<\/strong> <span>(.*?)<\/span> <\/li>/', $contact_info, $contact);
			preg_match_all('/<li> <strong class="mobile">(.*?)<\/strong> <span>(.*?)<\/span> <\/li>/', $contact_info, $contact_mobile);
            foreach($contact[2] as $index => $val){
                $contacts[$contact[1][$index]] = $val;
            }
			foreach($contact_mobile[2] as $index => $val){
                $contacts_mobile[$contact_mobile[1][$index]] = $val;
            }
			$data = array(
                'address' => $address,
                'properties' => $properties,
                'price' => $price,
                'user' => array(
                    'name' => $username,
                    'contact' => $contacts,
					'contact_mobile' => $contacts_mobile
                )
			);
            return $data;
        }
    }
    static function replaceSpace($string){
        $string = preg_replace("/\s+/", " ", $string);
        $string = trim($string);
        return $string;
    }
    static function Curl($url, $proxy = NULL){
        $options = array(CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYPEER => false
		);
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return str_replace(array("\n","\r","\t"), NULL, $header['content']);
    }
}
