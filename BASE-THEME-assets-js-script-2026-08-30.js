var $$ = (pr, el) => {
  return pr.querySelectorAll(el);
};
var $ = (pr, el) => {
  return pr.querySelector(el);
};
const removeCl = (el, cl) => {
  if (!el) return;
  if (el.length == undefined) {
    el?.classList.remove(cl);
  } else {
    el?.forEach(it => {
      it.classList.remove(cl);
    });
  }
};
const addCl = (el, cl) => {
  if (!el) return;
  if (el.length == undefined) {
    el?.classList.add(cl);
  } else {
    el?.forEach(it => {
      it.classList.add(cl);
    });
  }
};
const removeClPr = (pr, el, cl) => {
  if (!el) return;
  let mbr = el.length;
  if (mbr == undefined) {
    el?.closest('.' + pr)?.classList.remove(cl);
  } else {
    el?.forEach(it => {
      it.closest('.' + pr)?.classList.remove(cl);
    });
  }
};
const addClPr = (pr, el, cl) => {
  if (!el) return;
  let mbr = el.length;
  if (mbr == undefined) {
    el?.closest('.' + pr)?.classList.add(cl);
  } else {
    el?.forEach(it => {
      it.closest('.' + pr)?.classList.add(cl);
    });
  }
};
const $A = (w,l,h,b,o) => {
  if(!w) return;
  if(!h) return;
  if(!b) return;
  let wp = document.querySelectorAll(w+':not('+w+' '+w+')');
  let it_all = document.querySelectorAll(w+' > *');
  let use = {
    class_wp: 'accordion-wrapper',
      class_active: 'active',
      class_item: 'accordion-item',
      class_sub: 'item-has-children'
  }
  var c_ = (it,val) => {
      it.setAttribute('style','--sh:'+val+'px');
  };
  wp?.forEach(el => {
      let subs = el.querySelectorAll(w);
      if(subs.length > 0) {
          el.classList.add(use.class_sub);
      }
      el.classList.add(use.class_wp);
      if(!o)return;
      let itFirst = el.querySelectorAll(l);
      let bd = itFirst[0]?.querySelector(b);
      itFirst[0]?.classList.add(use.class_active);
      let timer = setInterval(()=> {
        clearInterval(timer);
        c_(bd,bd.scrollHeight);
      },500);
  });
  it_all?.forEach(line => {
    let Gp_ = line.closest(w)
    line.classList.add(use.class_item);
    it_header  = line.querySelector(h);
    it_header?.addEventListener('click', (e)=> {
      e.preventDefault();
      e = e.currentTarget;
      var W_ = e.closest(w+':not('+w+' '+w+')');
      var p = e.closest(l+'.'+use.class_item);
      var gp = e.closest('.'+use.class_sub);
      let els = [];
      let current_h = [];
          gp ? loop(use.class_sub) : loop(use.class_wp);
          function loop(cl) {
              while (p) {
                  if(p.classList.contains(cl)) break;
                  if(p.classList.contains(use.class_item))  {
                      if (els.indexOf(p) != -1) continue;
                      els.unshift(p);
                      let bd = p.querySelector(b);
                      current_h.push(bd.scrollHeight);
                      var sum = current_h.reduce((a, b) => {
                          return a + b;
                      });
                      c_(bd,sum);
                  }
                  p = p.parentNode;
              } 
          }
          e.closest('.'+use.class_item)?.classList.toggle(use.class_active);
          
          let it_ = Gp_.querySelectorAll(w+' > *');
          it_?.forEach(function (ex) {
              let pr_ex = ex.closest(l);
              if (els.indexOf(pr_ex) >= 0) return;
              pr_ex?.removeAttribute('style');
              pr_ex?.classList.remove(use.class_active);          
          });
      })
  });
};
const $T = (wp, tb, itbs, bd, cl, Id) => {
  var site_url = window.location.href;
  var site_slug = site_url.split('#')[1];
  var w = $$(document, '.' + wp);
  var addId = (el, id) => {
    if (!Id) return;
    if (!el) return;
    el.id = id;
  };
  var add_index = (els) => {
    if (window.innerWidth <= 767 ) {
      els.forEach((it,i) => {
        let index = i%2;
        it.dataset.index = index;
      });
    }else {
      els.forEach((it,i) => {
        let index = i%3;
        it.dataset.index = index;
      });
    }
  };
  var addHeight = (el) => {
    if (!el) return;
    el.forEach(it => {
      let h = it?.scrollHeight;
      let w = it?.scrollWidth;
      it.setAttribute('style','--h:'+h+'px;--w:'+w+'px;')
    });
  };

  // Current tab check in URL
  var tbl_a = [];
  if (site_slug != undefined) {
    w?.forEach(elem => {
      let ts = $$(elem, '.' + tb + ' .' + itbs + ' a[href*="#"]');
      if (!ts) return;
      ts.forEach(t => {
        let a_url = t.href;
        let a_slug = a_url.split('#')[1];
        if (site_slug.indexOf(a_slug) < 0) return;
        if (tbl_a.indexOf(elem) != -1) return;
        tbl_a.push(elem);
      });
    });
  }

  // Filter simple
  w?.forEach(elem => {
    let tabs = $$(elem, '.' + tb + ' .' + itbs + ' > a[href*="#"]');
    if (elem != tbl_a[0]) {
      let t = $(elem, '.' + tb + ' .' + itbs + ' > a');
      let a_url = t.href;
      let a_slug = a_url.split('#')[1];
      let contents = $$(elem, '.' + bd + ' > [data-slug*="' + a_slug + '"]');
      addClPr(itbs, t, cl);
      addCl(contents, cl);
      addId(elem, a_slug);
      add_index(contents);
    } else {
      if (site_slug == undefined) {
        let t = $(elem, '.' + tb + ' .' + itbs + ' > a');
        let a_url = t.href;
        let a_slug = a_url.split('#')[1];
        let contents = $$(elem, '.' + bd + ' > [data-slug*="' + a_slug + '"]');
        addClPr(itbs, t, cl);
        addCl(contents, cl);
        addId(elem, a_slug);
        add_index(contents);
      } else {
        tabs.forEach(t => {
          let a_url = t.href;
          let a_slug = a_url.split('#')[1];
          if (a_slug == site_slug) {
            let contents = $$(elem, '.' + bd + ' > [data-slug*="' + a_slug + '"]');
            addClPr(itbs, t, cl);
            addId(elem, a_slug);
            contents.forEach(c => {
              addCl(c, cl);
            });
            add_index(contents);
          }
        });
      }
    }

    // Event click
    tabs?.forEach(t => {
      t.onclick = (e) => {
        tb_el = e.currentTarget;
        let a_url = tb_el.href;
        let a_slug = a_url.split('#')[1];
        let tab = $$(elem, '.' + tb + ' .' + itbs + ' > a[href="#' + a_slug + '"]');
        let tab_other = $$(elem, '.' + tb + ' .' + itbs + ' > a:not([href="#' + a_slug + '"])');
        let contents = $$(elem, '.' + bd + ' > [data-slug*="' + a_slug + '"]');
        let contents_other = $$(elem, '.' + bd + ' > [data-slug]:not([data-slug*="' + a_slug + '"])');
        add_index(contents);
        addClPr(itbs, tab, cl);
        removeClPr(itbs, tab_other, cl);
        addCl(contents, cl);
        removeCl(contents_other, cl);
        addId(elem, a_slug);
        load_height();
      }
    });

    function load_height() {
      // Add Height
      let ctn_all = $$(elem, '.' + bd + ' > [data-slug]');
      addHeight(ctn_all);
    }
    load_height();
  });
};
function fixed_header() {
  function scroll_load() {
    if (document.body.scrollTop > 2 || document.documentElement.scrollTop > 2) {
      document.body.classList.add('sticky');
    } else {
      document.body.classList.remove('sticky');
    }
  }
  window.onscroll = scroll_load;
  scroll_load();
}
function btn_toggle_nav(w, l, b) {
  const wp_menu = document.querySelectorAll('.' + l);
  var new_class = { close: 'link-back prev', sb: 'sub-menu', tg: 'dropdown', span: 'arrow-see', classitemdropdown: 'item-has-sub-children', show: 'show', overflow: 'hidden' };
  wp_menu?.forEach(el => {
    var pr = el.closest('.' + w);
    if (pr) {
      var lis_hv_sub = pr.querySelectorAll('.' + new_class.sb);
      var btn = pr.querySelector('.' + b);
      btn?.addEventListener('click', function (e) {
        e.preventDefault();
        document.body.classList.toggle(new_class.overflow);
        pr?.classList.toggle(new_class.show);
        if (pr.classList.contains(new_class.show)) return;
        var sub = el.querySelectorAll('.' + l + ' .' + new_class.sb);
        sub?.forEach(function (ex) {
          ex.classList.add(new_class.sb)
          let pr_ex = ex.closest('li');
          if (!pr_ex.classList.contains(new_class.show)) return;
          pr_ex?.classList.remove(new_class.show);
        });
      });
      document.addEventListener('click', function (e) {
        if (pr !== e.target && !pr.contains(e.target)) {
          pr?.classList.remove(new_class.show);
          document.body.classList.remove(new_class.overflow);
          lis_hv_sub?.forEach(function (ex) {
            let pr_ex = ex.closest('li');
            pr_ex?.classList.remove(new_class.show);
          });
        }
      });
      loop_menu_list(el);
    } else {
      loop_menu_list(el);
    }
  });
  function loop_menu_list(m) {
    var sub = m.querySelectorAll('.' + l + ' .' + new_class.sb);
    sub?.forEach(el => {
      var span = document.createElement('span');
      var p = el.closest('li');
      span.classList.add(new_class.span);
      el.insertAdjacentElement('beforebegin', span);
      if (!p.classList.contains(new_class.classitemdropdown)) {
        p.classList.add(new_class.classitemdropdown, 'arrow-ev');
      }
      span.onclick = (e) => {
        e.preventDefault();
        let tr = e.currentTarget;
        var p = tr.closest('li');
        let els = [];
        let current_h = [];
        while (tr) {
          if (tr.tagName.toLowerCase() == 'nav') break;
          if (tr.tagName.toLowerCase() == 'li') {
            if (els.indexOf(tr) != -1) continue;
            els.unshift(tr);
            var sb = tr.querySelector('.' + new_class.sb);
            current_h.push(sb.scrollHeight);
            var sum = current_h.reduce((a, b) => {
              return a + b;
            });
            tr.setAttribute('style', '--sh:' + sum + 'px');
          }
          tr = tr.parentNode;

        }
        p?.classList.toggle(new_class.show);
        sub?.forEach(function (ex) {
          let pr_ex = ex.closest('li');
          if (els.indexOf(pr_ex) >= 0) return;
          pr_ex?.removeAttribute('style');
          pr_ex?.classList.remove(new_class.show);
        });
      };
      p.onmouseover = p.onmouseout = (e) => {
        if (!(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent))) {
          if (e.type == 'mouseover') {
            e.currentTarget?.classList.add(new_class.show);
            let tr = e.currentTarget;
            var p = tr.closest('li');
            let els = [];
            let current_h = [];
            while (tr) {
              if (tr.tagName.toLowerCase() == 'nav') break;
              if (tr.tagName.toLowerCase() == 'li') {
                if (els.indexOf(tr) != -1) continue;
                els.unshift(tr);
                var sb = tr.querySelector('.' + new_class.sb);
                current_h.push(sb.scrollHeight);
                var sum = current_h.reduce((a, b) => {
                  return a + b;
                });
                tr.setAttribute('style', '--sh:' + sum + 'px');
              }
              tr = tr.parentNode;

            }
          }
          if (e.type == 'mouseout') {
            e.currentTarget?.classList.remove(new_class.show);
          }
          console.log('mouse');
        }
      };
    });
  }
}
function addClass_sc_2col_figure(sl) {
  let el = document.querySelectorAll(sl);
  if(!el) return;
  el?.forEach(el => {
    let fig = el.querySelectorAll('.bx-lf > figure');
    if(!fig) return;
    if(fig.length < 2) return;
    el.classList.add('sc-has-2figure');
    for (let i = 0; i < fig.length; i++) {
      fig[i].classList.add('wp-img');
    }
  });
}
fixed_header();
//=>          : container  , Ul primary, Bouton show
btn_toggle_nav('header-nav', 'primary', 'wp-toggle');
addClass_sc_2col_figure('.sc-2col-classic-img-txt');
$A('.accordion-list','li','.accordion-header','.accordion-body',true);
//=>    : container, wrapper tab, tab  ,body-text, class curr, Add Id on section
$T('sc-tabs', 'wp-tabs', 'tab', 'lst-card', 'active', false);
$T('wp-tab', 'tab', 'sld-1col', 'active', false);
(($) => {
  $('.sld-1col .wp-sld').each(function() {
    $(this).owlCarousel({
      items:1,
      margin:0,
      loop:true,
      dots: false,
      responsiveClass:true,
    });
  });
  
  $('.sld-2col-arrow .wp-sld').owlCarousel({
    loop:false,
    margin:16,
    dots: false,
    responsiveClass:true,
    responsive:{
      0:{
        items:1,
        nav:true
      },
      1200:{
        items:3,
        nav:false
      }
    }
  });
  
  
  
  
  if ($('.Grid4Lay').length > 0) {
  
	var cards = $(".card.Hop");

	// Trie les éléments en fonction de la valeur de l'attribut "datetime"
	cards.sort(function(a, b) {
	  var timeA = parseInt($(a).find("time").attr("datetime"));
	  var timeB = parseInt($(b).find("time").attr("datetime"));
	  return timeA - timeB;
	});

	// Boucle sur les éléments triés et les ajoute à leur parent dans l'ordre trié
	$.each(cards, function(index, card){
	  $(card).parent().append(card);
	});


    $('.Grid4Lay').owlCarousel({
    loop:false,
	center: true,
    margin:16,
    dots: false,
    responsiveClass:true,
      responsive:{
      0:{
        items:1,
        nav:false,
      },
      600:{
        items:3,
      },
      1680:{
        items:3,
      }
    }
  });
  
  
  	
} 	


  
  $('.sc-4col-sld-txt:not([id]) .wp-sld').owlCarousel({
    nav: false,
    loop: true,
    margin: 16,
    dots: false,
    center: true,
    autoplay: true,
    responsiveClass:true,
    URLhashListener:true,
    responsive:{
      0:{
        items:1,
        nav:false,
      },
      600:{
        items:3,
      },
      1680:{
        items:4,
      }
    }
  });
  

if ($('.wp-video').length > 0) {

$( ".wp-video" ).click(function() {
  $('.wp-video').addClass('videoAfter');
  $('.wp-video.videoAfter video').get(0).play();
});

}


$(document).ready(function () {
    if ($('.marquee-content').length > 0) {
        let $marquee = $('.marquee-content');

        // Attendre un léger délai pour assurer que la largeur est bien calculée
        setTimeout(function () {
            const div = document.querySelector(".marquee-content");
            const width = div.offsetWidth; // Récupérer la largeur réelle

            // Créer un <style> si inexistant
            let style = document.getElementById("dynamic-style");
            if (!style) {
                style = document.createElement("style");
                style.id = "dynamic-style";
                document.head.appendChild(style);
            }

            // Insérer l'animation dans la balise <style>
            style.innerHTML = `
                @keyframes marquee {
                    from { transform: translateX(${width}px); }
                    to { transform: translateX(-${width}px); }
                }
                
            `;

            console.log(`Animation mise à jour avec width = ${width}px`);

        }, 100); // Délai court pour garantir le bon calcul des dimensions

        // Mettre l'animation en pause au survol
        $('.marquee-container').hover(
            function () { $marquee.css('animation-play-state', 'paused'); },
            function () { $marquee.css('animation-play-state', 'running'); }
        );
    }
});



if ($('#InfoWidget').length > 0) {

$(window).on('load', function() {
    var imgwidget = $('.weather-customize .bwop-icon').attr('class');
    var degres = $('.weather-customize .booked-bwop-number').text();
	var temps = $('.weather-customize .bwop-state').text();
  
  $('#InfoWidget strong em').html(degres + '°C'); 
  $('#InfoWidget strong span').html(temps); 
  $('#InfoWidget .imgMeteo').addClass(imgwidget); 


});

	
}	

if ($('.flex-xbetween.map').length > 0) {
$('.flex-xbetween.map a.arrow-see').remove(); 
}

	
	



   var currentPath = window.location.pathname;

        // Function to remove language prefix if present
        function stripLangPrefix(path) {
            return path.replace(/^\/(en|de)/, '');
        }

        var strippedPath = stripLangPrefix(currentPath);

        $('.nav-list li a').each(function(){
            var href = $(this).attr('href');
            if(stripLangPrefix(href) === strippedPath) {
                $(this).parent().addClass('active');
            }
        });
	
	
	
	
	
	if ($('#wpcf7-f806-o1').length > 0) {
	
	
		
	$('.nbrenfants, .nbradultes').change(function() {
		var nbrenfants = Number($('.nbrenfants').val());
		var nbradultes = Number($('.nbradultes').val());

		// Calcul du nombre d'adultes payants
		var nbradultgratuit = Math.floor(nbrenfants/10); // on divise par 10 et on arrondit à l'entier inférieur
		$('.nbradultgratuit').val(nbradultgratuit);

		// Mise à jour du nombre d'adultes payants
				var nbradultpayant = Math.max(0, nbradultes - nbradultgratuit);
		$('.nbradultpayant').val(nbradultpayant);

		// Calcul des prix
		var nbrprixenfants = nbrenfants * 6;
		var nbrprixadultes = nbradultpayant * 8.50;

		// Affichage des prix
		$('.nbrprixenfants').val(nbrprixenfants);
		$('.nbrprixadultes').val(nbrprixadultes);
		$('.totalprixscolaire').val(nbrprixenfants + nbrprixadultes + ' €');
	});




	$('.nbrpersohandicape, .nbraccompa').change(function() {
		var nbrpersohandicape = Number($('.nbrpersohandicape').val());
		var nbraccompa = Number($('.nbraccompa').val());

		// Calcul des prix
		var nbrpershandicape = nbrpersohandicape * 6;
		var nbrbilletaccompa = nbraccompa * 6;

		// Affichage des prix
		$('.nbrpershandicape').val(nbrpershandicape);
		$('.nbrbilletaccompa').val(nbrbilletaccompa);
		$('.totalprixhandicape').val(nbrpershandicape + nbrbilletaccompa + ' €');
	});



	$('.nbrenfantsmoins14, .nbradultemoins14').change(function() {
		var nbrenfantsmoins14 = Number($('.nbrenfantsmoins14').val());
		var nbradultemoins14 = Number($('.nbradultemoins14').val());

		// Calcul des prix
		var nbrbilletenfant14 = nbrenfantsmoins14 * 6;
		var nbrbilletadulte14 = nbradultemoins14 * 8.50;

		// Affichage des prix
		$('.nbrbilletenfant14').val(nbrbilletenfant14);
		$('.nbrbilletadulte14').val(nbrbilletadulte14);
		$('.totalbilletenfants14').val(nbrbilletenfant14 + nbrbilletadulte14 + ' €');
	});


}
	
	
	
	
	var SipasdeHop = jQuery('.sc-filter-product .Grid4Lay .Hop').length; 
	if(SipasdeHop === 0) { 
	jQuery('.sc-filter-product').remove();
	}
	
	



	
})(jQuery);