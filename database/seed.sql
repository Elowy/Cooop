-- Net Trade – demó adatok

INSERT INTO categories (slug, name, icon) VALUES
('routerek', 'Routerek', 'router'),
('switchek', 'Switchek', 'switch'),
('kabelek',  'Kábelek',  'cable'),
('kamerak',  'Kamerák',  'camera'),
('tarolok',  'Tárolók',  'nas');

INSERT INTO products (category_id, slug, name, short, description, price, image, stock, featured) VALUES
((SELECT id FROM categories WHERE slug='routerek'), 'wifi6-router-ax3000', 'WiFi 6 Router AX3000', 'Nagy sebességű WiFi 6 router otthonra és kis irodába.', 'Dual-band WiFi 6 (802.11ax) router 3000 Mbps összesített sebességgel, 4 db Gigabit LAN porttal és OFDMA technológiával.', 28990, 'router.svg', 24, 1),
((SELECT id FROM categories WHERE slug='switchek'), 'gigabit-switch-8-port', 'Gigabit Switch 8 portos', 'Fémházas, csendes 8 portos Gigabit switch.', 'Plug & play 8 portos Gigabit Ethernet switch, fémházban, ventilátor nélküli csendes működéssel.', 12490, 'switch.svg', 50, 1),
((SELECT id FROM categories WHERE slug='kabelek'), 'cat6-utp-kabel-305m', 'Cat6 UTP kábel 305m', 'Réz CAT6 UTP installációs kábel dobozos kiszerelésben.', '305 méteres CAT6 UTP installációs kábel, tömör réz erekkel, 250 MHz sávszélességgel.', 34900, 'cable.svg', 15, 0),
((SELECT id FROM categories WHERE slug='kamerak'), 'poe-ip-kamera-4mp', 'PoE IP kamera 4MP', '4 megapixeles kültéri PoE IP biztonsági kamera.', '4MP felbontású kültéri (IP67) PoE IP kamera éjjellátóval (30m IR), mozgásérzékeléssel és H.265 tömörítéssel.', 19990, 'camera.svg', 32, 1),
((SELECT id FROM categories WHERE slug='routerek'), 'access-point-ceiling-ax1800', 'Access Point mennyezeti AX1800', 'Mennyezetre szerelhető WiFi 6 access point.', 'Mennyezetre szerelhető WiFi 6 access point 1800 Mbps sebességgel, PoE táplálással.', 23490, 'ap.svg', 18, 0),
((SELECT id FROM categories WHERE slug='kabelek'), 'patch-panel-24-port', 'Patch panel 24 portos', '19" 1U CAT6 patch panel rackszekrénybe.', '19 colos, 1U magas, 24 portos CAT6 patch panel rendezett hálózati szereléshez.', 8990, 'panel.svg', 40, 0),
((SELECT id FROM categories WHERE slug='tarolok'), 'nas-2-bay', 'NAS adattároló 2 lemezes', 'Kétlemezes hálózati adattároló otthonra és irodába.', 'Kétlemezes (2-bay) NAS központi adattároláshoz, RAID támogatással és gigabites csatlakozással.', 64900, 'nas.svg', 9, 1),
((SELECT id FROM categories WHERE slug='tarolok'), 'szunetmentes-tapegyseg-650va', 'Szünetmentes tápegység 650VA', 'UPS a hálózati eszközök védelméhez áramkimaradás ellen.', '650VA / 360W szünetmentes tápegység (UPS) túlfeszültség-védelemmel.', 17990, 'ups.svg', 21, 0);
