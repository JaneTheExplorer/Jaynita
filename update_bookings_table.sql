-- Add new columns to bookings table for enhanced functionality
ALTER TABLE bookings 
ADD COLUMN passengers_count INT DEFAULT 1,
ADD COLUMN class_type VARCHAR(20) DEFAULT 'economy';

-- Update existing bookings with default values
UPDATE bookings SET passengers_count = 1 WHERE passengers_count IS NULL;
UPDATE bookings SET class_type = 'economy' WHERE class_type IS NULL;
