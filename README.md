# Carmo Category Rundown

WordPress block: WooCommerce **product category** carousel. Image cards with the category name in a pill; on desktop hover, the name in large type, the category description and a "See all" button. Sibling of [carmo-category-product](https://github.com/carmopereira/carmo-category-product).

## Block settings

- **Categories:** top-level, subcategories of a parent, or hand-picked (in pick order)
- **Order:** WooCommerce custom order, name or product count
- **Columns:** desktop (1–6) and mobile (1–3), card proportion
- **Text:** title / subtitle, description character limit, button text

## Development

```bash
npm install
npm start          # watch
npm run build      # bumps the patch version and syncs it to the PHP header, block.json and readme.txt
npm run symlink    # link into a local WordPress plugins folder
npm run updateGIT  # add, commit and push
```

Scaffolded with `@wordpress/create-block --variant=dynamic` and `@carmopereira/wp-plugin-addons`.
