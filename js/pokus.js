;(function( $ ) {

    // Zde budou metody, které může z plug-inu zavolat i sám uživatel
    var methods = {
        // Funkce nastaví aktivní slide (Index od 0 do počtu obrázků v galerii) -
        active: function( index, direction ) {
            // Nastavíme rychlost
            speed = $(this).data('speed') || 800;
            // Nastavíme směry efektů
            directionHide = direction || 'left';
            directionShow = directionHide == 'left' ? 'right' : 'left';
            // Skryjeme aktivní položku
            $(this).find('li.active').hide('slide', {
                direction: directionHide
            }, speed);
            // Všem položkám odstraníme třídu .active
            $(this).find('li').removeClass('active');
            // Načteme activní slide
            var slide = $(this).find('li').get(index) || false;
            // Zobrazíme ho
            $(slide).addClass('active').show('slide', {
                direction: directionShow
            }, speed);
            // Vrátíme aktivní element
            return $(this).find('li').get(index) || false;
        },

        // Přesune se na další slide
        next: function() {
            // Najdeme další element a zjistíme jeho index, ke kterému přičteme +1
            var index = ($(this).find('li.active').index()+1);
            // Aktivujeme tento slide, pokud existuje. Pokud ne, automaticky se přesuneme na první (nultý)
            return $(this).editable("active", ($(this).find('li').get(index) ? index : 0));
        },

        // Přesune se na předchozí slide
        prev: function() {
            var index = $(this).find('li.active').index()-1 < 0 ?$(this).find('li').length-1 : $(this).find('li.active').index()-1;
            // Aktivujeme slide s títo indexem
            return $(this).editable("active", index, 'right');
        },

        // Inicializační metoda - je volána z hlavní funkce
        init: function( o ) {
            // Získáme nastavené volby plug-inu
            o = $.extend({}, $.fn.editable.defaults, o);
            
            // Uložíme si aktuální kontext funkce (element plug-inu)
            var $that = $(this);

            // Funkce po kliknutí na šipky
            var openEditor = function( e ) {
                alert('open');
                e = $.event.fix(e);
                e.preventDefault();
//                $that.editable("next");
            };
            return this.each(function() {
                $self = $(this);
                var input = $("<input type=\"text\" />");
//                input.css({
//                    display: 'none'
//                });

                var $arrowLeft = $('<a >aaa</a>').attr('href', '#left').addClass('arrowLeft');
                $self.before( $arrowLeft );
//                this.bind('click', openEditor)
                $self.after( input );
            });
            
            
//            // Projdeme všechny předané elementy a inicializujeme pro ně plug-in
//            return this.each(function() {
//                alert(dump(this, 2));
//                // Najdeme všechny obrázky ve slideru
//                var $items = $(this).find('li'),
//                count = $items.length,
//                $self = $(this);
//
//                // Všechny je skryjeme a pozicujeme absolutně
//                $items.css({
//                    display: 'none', 
//                    position: 'absolute'
//                });
//
//                // Vložíme seznamu třídu pro přístupnější manipulování v CSS
//                $self.addClass('editable-content').data('speed', o.speed);
//
////                // Aktivujeme první element
////                $($items.get(o.active)).addClass("active");
//
//                // Vytvoříme si postraní šipky pro posun slideru
//                var $arrowRight = $('&lt;a /&gt;').attr('href', '#right').addClass('arrowRight');
//                // Nastavíme callback na kliknutí
//                $arrowRight.bind('click', right_click);
//                // Vložíme je před seznam slidů
//                $self.before( $arrowRight );
//            });
        }
    };

    // Vstupní funkce plug-inu
    $.fn.editable = function( method ) {
        // Pokud máme jméno funkce, kterou chceme zavolat
        if ( methods[method] ) {
            // Zavoláme danou funkci se všemi ostatními předanými argumenty plug-inu
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if ( typeof method == 'object' || !method ) {
            // Pokud ne, zavoláme inicializační metodu
            return methods.init.apply(this, arguments);
        } else {
            // Pokud metoda neexistuje, vypíšeme chybu
            $.error('Metoda ' + method + ' neexistuje v plug-inu jQuery.editable');
        }
    };

    // Základní nastavení
    $.fn.editable.defaults = {
    };
})( jQuery ); // zavoláme funkce s parametrem jQuery

