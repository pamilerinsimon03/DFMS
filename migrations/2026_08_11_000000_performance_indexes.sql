-- Standalone Migration: Production Performance Composite Indexes for DFMS Scale (25,000+ Students)
-- Run this on existing live databases to apply performance indexes without dropping tables.

-- 1. Composite Index for Verification Queue Filtering
CREATE INDEX IF NOT EXISTS idx_docs_dept_status_created 
ON documents (department_id, status, created_at DESC);

-- 2. Composite Index for Student Document Dashboard
CREATE INDEX IF NOT EXISTS idx_docs_user_status 
ON documents (user_id, status);

-- 3. Composite Index for Multi-parameter Student Directory Filters
CREATE INDEX IF NOT EXISTS idx_users_dept_level 
ON users (department_id, level, program_type, mode);

-- 4. Rate Limiting Table Cleanup Index
CREATE INDEX IF NOT EXISTS idx_rate_limits_key_time 
ON rate_limits (key_hash, timestamp);
