# Release Notes

## Version 1.0.0 (October 26, 2024)

### New Features
- Added support for integration with external APIs.
- Implemented social login functionality.

### Bug Fixes
- Fixed authentication issue that caused login failures.
- Resolved dashboard display error.

### Improvements
- Enhanced page loading performance.
- Updated the design of the admin panel.

---

## Version 1.0.3 (October 30, 2024)

### New Features
- Introduced a command system for creating validations.
- Added support for the sheet-vest plugin, enabling streamlined frontend development with Vite.
- Launched Brow, a simplified and efficient command-line manager for project management.

### Bug Fixes
- Resolved issues with migration handling to ensure smoother database updates.

### Improvements
- Removed unnecessary resources to optimize project size and performance.
- Established a foundational structure for "makes," facilitating more organized project creation.
- Introduced a dynamic blueprint for migrations, offering more flexibility in defining database schema.
- Added an option to choose between UUID or ID for applications, supporting more versatile identification setups

## Upgrade Instructions
To upgrade to version 1.0.3, please follow these steps:
1. Backup your project to prevent data loss.
2. Download version 1.0.3 from the repository.
3. Run `composer update` to install the dependencies.