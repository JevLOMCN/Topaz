import { type GetServerSidePropsContext } from 'next'

function generateSiteMap(
  config: {
    host: string
    lastMod: string
  },
  pages: string[]
) {
  return `<?xml version="1.0" encoding="UTF-8"?>
   <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
     <url>
       <loc>https://${config.host}</loc>
        <lastmod>${config.lastMod}</lastmod>
        <changefreq>daily</changefreq>
     </url>
     ${pages
       .map((page) => {
         return `
       <url>
          <link rel="canonical" href="${page}" />
          <loc>${page}</loc>
       </url>
     `
       })
       .join('')}
   </urlset>
 `
}

function SiteMap() {}

export async function getServerSideProps({
  req,
  res,
}: GetServerSidePropsContext) {
  const host = req.headers.host ?? 'mir4tools'
  const lastMod = new Date().toISOString()
  const pages = ['home', 'xp']

  const config = { host, lastMod }

  const sitemap = generateSiteMap(config, pages)

  res.setHeader('Content-Type', 'text/xml')
  res.write(sitemap)
  res.end()

  return {
    props: {},
  }
}

export default SiteMap
