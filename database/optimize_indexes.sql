-- TecDoc Database Optimization Indexes
-- Run this to dramatically improve search performance

USE tecdoc2024q4;

-- Check existing indexes first
SHOW INDEX FROM ARTICLES;

-- Create optimized indexes for search performance
-- 1. Brand search optimization
CREATE INDEX IF NOT EXISTS idx_articles_brand_search ON ARTICLES(ART_SUP_BRAND);

-- 2. Article number search optimization  
CREATE INDEX IF NOT EXISTS idx_articles_number_search ON ARTICLES(ART_ARTICLE_NR);

-- 3. Combined search optimization (most important)
CREATE INDEX IF NOT EXISTS idx_articles_brand_number ON ARTICLES(ART_SUP_BRAND, ART_ARTICLE_NR);

-- 4. Supplier relationship optimization
CREATE INDEX IF NOT EXISTS idx_articles_supplier ON ARTICLES(ART_SUP_ID);

-- 5. CTM field optimization (for sorting)
CREATE INDEX IF NOT EXISTS idx_articles_ctm ON ARTICLES(ART_CTM);

-- 6. Composite index for pagination optimization
CREATE INDEX IF NOT EXISTS idx_articles_pagination ON ARTICLES(ART_SUP_BRAND, ART_ARTICLE_NR, ART_ID);

-- Manufacturer table optimization
CREATE INDEX IF NOT EXISTS idx_manufacturers_brand ON MANUFACTURERS(MFA_BRAND);
CREATE INDEX IF NOT EXISTS idx_manufacturers_pc_mfa ON MANUFACTURERS(MFA_PC_MFA);

-- Model Series optimization
CREATE INDEX IF NOT EXISTS idx_models_mfa ON MODELS_SERIES(MOD_MFA_ID);
CREATE INDEX IF NOT EXISTS idx_models_pc_mod ON MODELS_SERIES(MOD_PC_MOD);

-- Passenger Cars optimization
CREATE INDEX IF NOT EXISTS idx_passenger_cars_mod ON PASSENGER_CARS(PC_MOD_ID);
CREATE INDEX IF NOT EXISTS idx_passenger_cars_mfa ON PASSENGER_CARS(PC_MFA_ID);

-- Show created indexes
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    CARDINALITY
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'tecdoc2024q4' 
    AND TABLE_NAME IN ('ARTICLES', 'MANUFACTURERS', 'MODELS_SERIES', 'PASSENGER_CARS')
    AND INDEX_NAME LIKE 'idx_%'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Performance analysis query
EXPLAIN SELECT COUNT(*) FROM ARTICLES WHERE ART_SUP_BRAND LIKE '%BOSCH%';
