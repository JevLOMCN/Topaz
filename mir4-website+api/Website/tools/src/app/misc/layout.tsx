import MainBackground from '@/components/global/MainBackground'
import GlobalNavbar from '@/components/global/Navbar'
import { RouteMetadata } from '../DefaultMetadata'

export const metadata = RouteMetadata.Miscellaneous

export default function Layout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-screen w-full flex-col font-main">
      <MainBackground />
      <GlobalNavbar />
      <div className="relative mx-auto flex w-full max-w-7xl flex-col items-center px-5 py-12 pt-40 selection:bg-primary-800 md:pl-20 lg:pt-48 [&>div]:mx-auto [&>div]:flex [&>div]:h-full [&>div]:w-full [&>div]:flex-col [&>div]:items-center">
        {children}
      </div>
    </div>
  )
}
