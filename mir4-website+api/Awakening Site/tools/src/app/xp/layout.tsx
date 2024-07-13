import MainBackground from '@/components/global/MainBackground'
import GlobalNavbar from '@/components/global/Navbar'
import ExperienceNavExtesion from '@/components/xp/NavbarExtension'
import { RouteMetadata } from '../DefaultMetadata'

export const metadata = RouteMetadata.ExperienceCalculator

export default function Layout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-screen w-full flex-col font-main">
      <MainBackground />
      <GlobalNavbar>
        <ExperienceNavExtesion />
      </GlobalNavbar>
      <div className="relative md:pl-20 px-5 pt-44 md:pt-24 max-w-7xl mx-auto flex w-full flex-col items-center py-8 selection:bg-primary-800 [&>div]:mx-auto [&>div]:flex [&>div]:h-full [&>div]:w-full [&>div]:flex-col [&>div]:items-center">
        {children}
      </div>
    </div>
  )
}
