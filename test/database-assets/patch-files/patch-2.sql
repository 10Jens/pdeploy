CREATE TABLE `state` (
  `name` char(2) NOT NULL,
  `abbrev` varchar(255) NOT NULL
);

CREATE TABLE `zip_code` (
  `zip` char(5) NOT NULL,
  `state` char(2) NOT NULL,
  `city` varchar(255) NOT NULL
);
