-- Net-Trade Hungary – demó adatok (Vega madáretetők)

INSERT INTO categories (slug, name, icon) VALUES
('klasszikus',   'Klasszikus',   'feeder'),
('modern',       'Modern',       'feeder'),
('nagy',         'Nagy méretű',  'feeder'),
('fuggesztheto', 'Függeszthető', 'feeder');

INSERT INTO products (category_id, slug, name, short, description, price, image, stock, featured) VALUES
((SELECT id FROM categories WHERE slug='klasszikus'), 'vega-klasszikus-madareteto', 'Vega klasszikus madáretető', 'Hőkezelt borovi fenyőből készült, időtálló klasszikus etető.', 'A Vega klasszikus madáretető praktikus megoldás, amely különlegessé teszi kertjét vagy erkélyét a téli hónapokban. Hőkezelt borovi fenyőből és farostlemezből készül, IPPC és ISPM 15 szabvány szerint, CE-tanúsítvánnyal.', 4990, 'feeder-classic.svg', 40, 1),
((SELECT id FROM categories WHERE slug='klasszikus'), 'vega-ketpalcas-madareteto', 'Vega kétpálcás madáretető', 'Két ülőpálcával a kisebb énekesmadaraknak.', 'Két ülőpálcával ellátott klasszikus madáretető, amely stabil, szellős etetőfelületet kínál a kisebb énekesmadaraknak. Hőkezelt borovi fenyőből, tartós kivitelben.', 5490, 'feeder-twobar.svg', 35, 0),
((SELECT id FROM categories WHERE slug='modern'), 'vega-pagoda-madareteto', 'Vega pagoda madáretető', 'Letisztult pagoda forma, modern kertek dísze.', 'A Vega pagoda madáretető letisztult, modern formavilágával bármely kert dísze lehet. Tágas etetőfelülete több madár egyidejű etetését is lehetővé teszi.', 6990, 'feeder-pagoda.svg', 22, 1),
((SELECT id FROM categories WHERE slug='modern'), 'vega-modern-madareteto', 'Vega modern madáretető', 'Minimalista vonalvezetés, natúr felület.', 'Minimalista vonalvezetésű, natúr felületű madáretető kortárs homlokzatokhoz és modern kertekhez. Hőkezelt borovi fenyőből készül.', 7490, 'feeder-modern.svg', 18, 0),
((SELECT id FROM categories WHERE slug='nagy'), 'vega-nagy-csaladi-madareteto', 'Vega nagy családi madáretető', 'Nagyobb befogadóképesség egész télre.', 'Nagy befogadóképességű madáretető, amely több madár egyidejű etetését teszi lehetővé egész télen át. Robusztus, hőkezelt borovi fenyő szerkezet.', 8990, 'feeder-large.svg', 14, 1),
((SELECT id FROM categories WHERE slug='fuggesztheto'), 'vega-fuggesztheto-madareteto', 'Vega függeszthető madáretető', 'Faágra vagy konzolra akasztható, kompakt etető.', 'Faágra vagy konzolra egyszerűen felakasztható, könnyű és kompakt madáretető a kertbe vagy a balkonra. Hőkezelt borovi fenyőből.', 4490, 'feeder-hanging.svg', 50, 1),
((SELECT id FROM categories WHERE slug='nagy'), 'vega-oszlopos-madareteto', 'Vega oszlopos madáretető', 'Talajba állítható oszlopos etető nyitott kertbe.', 'Talajba állítható, oszlopos kivitelű madáretető, amely nyitott kertbe, gyepre is kiváló. Stabil láb, tágas tető, hőkezelt borovi fenyőből.', 7990, 'feeder-post.svg', 12, 0),
((SELECT id FROM categories WHERE slug='fuggesztheto'), 'vega-mini-balkon-madareteto', 'Vega mini balkon madáretető', 'Helytakarékos mini etető erkélyre, ablakpárkányra.', 'Helytakarékos, mini méretű madáretető erkélyre vagy ablakpárkányra, ahol kevés a hely. Könnyen felakasztható, hőkezelt borovi fenyőből.', 3990, 'feeder-mini.svg', 60, 0);
