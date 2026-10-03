-- WildTrail Lanka v10 final rendering/data repair
-- Safe to run on an existing v9 database.
-- No tables are dropped and no booking/user data is removed.

UPDATE parks
SET name='Yala National Park',
    province='Southern Province',
    description='Dry forest, lagoons and open plains with one of Sri Lanka’s best-known leopard landscapes.',
    image_url='https://commons.wikimedia.org/wiki/Special:Redirect/file/Yala%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600',
    entry_fee=4500,
    daily_vehicle_cap=80,
    status='active'
WHERE slug='yala';

UPDATE parks
SET name='Udawalawe National Park',
    province='Sabaragamuwa Province',
    description='Open grasslands and reservoir edges renowned for close elephant encounters.',
    image_url='https://commons.wikimedia.org/wiki/Special:Redirect/file/Landscape%20in%20Udawalawe%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600',
    entry_fee=3500,
    daily_vehicle_cap=65,
    status='active'
WHERE slug='udawalawe';

UPDATE parks
SET name='Minneriya National Park',
    province='North Central Province',
    description='Ancient reservoir, grasslands and seasonal elephant gatherings.',
    image_url='https://commons.wikimedia.org/wiki/Special:Redirect/file/Minneriya%20National%20Park%2C%20Sri%20Lanka.jpg?width=1600',
    entry_fee=3200,
    daily_vehicle_cap=60,
    status='active'
WHERE slug='minneriya';

UPDATE parks
SET name='Wilpattu National Park',
    province='North Western Province',
    description='Sri Lanka’s largest national park, known for natural villus, sloth bears and leopards.',
    image_url='https://commons.wikimedia.org/wiki/Special:Redirect/file/Amazing%20Natural%20Landscapes.jpg?width=1600',
    entry_fee=4200,
    daily_vehicle_cap=50,
    status='active'
WHERE slug='wilpattu';
