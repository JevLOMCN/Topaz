import MainBackground from '@/components/global/MainBackground'
import GlobalNavbar from '@/components/global/Navbar'
import { RouteMetadata } from '../DefaultMetadata'

export const metadata = RouteMetadata.Maps

export default function Layout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex h-full min-h-screen w-full flex-col font-main">
      <MainBackground />
      <GlobalNavbar />
      {children}
    </div>
  )
}
