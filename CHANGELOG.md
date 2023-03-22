# CHANGELOG

# 1.1.2 - 2023-03-23

### Fixed

- Fix branch issue

# 1.1.1 - 2023-03-22

### Fixed

- Improve usage of credsPath

## 1.1.0 - 2022-08-23

### Added

- Add viewsPathLowercase in app.yaml.

## 1.0.9 - 2022-08-03

### Fixed

- Make sure AntiXssMiddleware handles json and form data.

## 1.0.8 - 2022-07-21

### Fixed

- Use autoDetectedTemplate() in Zkwbbr\View instead of our own.

## 1.0.7 - 2022-07-07

### Fixed

- Fix credentials path in ContainerService.

## 1.0.6 - 2022-07-06

### Fixed

- Use \getenv('APP_ENV') instead of .development_mode to determine if project is in dev mode.

## 1.0.5 - 2022-06-22

### Added

- Add ability to use custom layouts.
- Add Validators and Exceptions.

## 1.0.4 - 2022-06-07

### Fixed

- Change param #1 of getTraceAsStringUntruncated() to \Throwable in whoops.php.

## 1.0.3 - 2022-06-05

### Fixed

- Fixed Whoops issues in prod mode.

## 1.0.2 - 2022-06-04

### Fixed

- Fixed issues pointed out by phpstan.

## 1.0.1 - 2022-06-03

### Added

- Added logs/app/errors/ folder.
- Added keywords in composer.json.

### Changed

- Changed welcome message in Home in Views.

## 1.0.0 - 2022-06-03

- Release first version.