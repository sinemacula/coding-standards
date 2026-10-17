terraform {
  required_version = ">= 1.16"
}

locals {
  greeting = "Hello, ${var.name}"
}
