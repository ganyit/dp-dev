# AI Comment Moderation

This Drupal module uses OpenAI's Moderation API to automatically flag or unpublish inappropriate comments.

## Features

- Auto-moderates user comments
- Admin config page for OpenAI API key
- Hooks into comment entity pre-save

## Requirements

- Drupal 10
- OpenAI API key

## Installation

1. Place the module in `/modules/custom/`
2. Enable the module: `drush en ai_comment_moderation`
3. Configure at `/admin/config/content/ai-comment-moderation`

## Maintainers

- Your Name (your_drupal_org_username)
