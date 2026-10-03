-- WildTrail v18: customer-facing safari-time terminology
UPDATE park_slots SET label='Dawn' WHERE LOWER(label) IN ('dawn track','dawn');
UPDATE park_slots SET label='Morning' WHERE LOWER(label) IN ('morning track','morning');
UPDATE park_slots SET label='Afternoon' WHERE LOWER(label) IN ('afternoon track','afternoon');
