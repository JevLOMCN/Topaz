import { type Metadata } from 'next'

export const DefaultMetadata: Metadata = {
  applicationName: 'Mir4 Tools',
  referrer: 'origin-when-cross-origin',
  keywords: ['mir4', 'mir4 tools', 'mir4 calculator', 'mir4 exp calculator'],
  themeColor: '#473E65',
  openGraph: {
    url: 'https://www.mir4tools.com/',
    siteName: 'Mir4 Tools',
    images: [
      {
        url: '/seo/crafting-calculator.webp',
        width: 1200,
        height: 630,
      },
    ],
    locale: 'en-US',
    type: 'website',
  },
  icons: {
    icon: '/favicon.ico',
    shortcut: '/icon/icon-128x128.png',
    apple: '/icon/icon-512x512.png',
    other: {
      rel: 'apple-touch-icon',
      url: '/icon/apple-touch-icon.png',
    },
  },
  other: {
    'msapplication-TileColor': '#473E65',
    'msapplication-TileImage': '/favicon.ico',
    accessibilityFeature: [
      'largePrint/CSSEnabled',
      'highContrast/CSSEnabled',
      'resizeText/CSSEnabled',
      'displayTransformability',
      'longDescription',
      'alternativeText',
    ],
    accessibilityControl: ['fullKeyboardControl', 'fullMouseControl'],
    accessibilityHazard: ['noFlashingHazard', 'noMotionSimulationHazard'],
    accessibilityAPI: ['ARIA'],
  },
}

export const RouteMetadata = {
  CraftingCalculator: getSeo({
    title: 'Crafting Calculator',
    description:
      'A crafting tool to help players calculate their craft with precision and speed that includes advanced features such as an inventory system and a large variety of customization.',
    image: '/seo/crafting-calculator.webp',
    imageAlt: 'Crafting calculator banner',
  }),
  ExperienceCalculator: getSeo({
    title: 'Experience Calculator',
    description:
      'An experience-level calculator to help players measure and calculate their progress through the game with time and item cost predictions.',
    image: '/seo/experience-calculator.webp',
    imageAlt: 'Experience calculator banner',
  }),
  Conquests: getSeo({
    title: 'Conquests',
    description:
      'A tool used to show upgrade costs, time, and effects of conquest towers.',
    image: '/seo/conquests.webp',
    imageAlt: 'Conquests banner',
  }),
  Constitution: getSeo({
    title: 'Constitution',
    description:
      'A page used to calculate and show the effects of constitution levels.',
    image: '/seo/constitution.webp',
    imageAlt: 'Constitution banner',
  }),
  Maps: getSeo({
    title: 'Maps',
    description:
      'An interactive mir4 resource tracker to display energy, mining, chests, and dark steel nodes in maps and secret peaks on any floor.',
    image: '/seo/maps.webp',
    imageAlt: 'Maps banner',
  }),
  Miscellaneous: getSeo({
    title: 'Miscellaneous',
    description:
      'A place where you can access additional tools such as a mining speed calculator.',
    image: '/seo/misc.webp',
    imageAlt: 'Miscellaneous banner',
  }),
  InnerForce: getSeo({
    title: 'Inner Force',
    description:
      'A page used to calculate and show the effects of inner force levels.',
    image: '/seo/inner-force.webp',
    imageAlt: 'Inner Force banner',
  }),
}

export function getSeo({
  title,
  description,
  image,
  imageAlt,
}: {
  title: string
  description: string
  image: string
  imageAlt: string
}): Metadata {
  return {
    title,
    description,
    ...DefaultMetadata,
    openGraph: {
      ...DefaultMetadata.openGraph,
      title,
      description,
      images: image
        ? { url: image, secureUrl: image, alt: imageAlt }
        : DefaultMetadata.openGraph?.images,
    },
    twitter: {
      title,
      description,
      images: [{ url: image, alt: imageAlt }],
    },
    alternates: {
      canonical: '',
    },
    manifest: '/manifest.json',
  }
}
