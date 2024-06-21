import MapsBackground from '@/components/global/MapsBackground'
import GlobalNavbar from '@/components/global/Navbar'
import { RouteMetadata } from '../DefaultMetadata'

export const metadata = RouteMetadata.Maps

export default function Layout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex bg-black h-full min-h-screen w-full flex-col font-main">
      <MapsBackground />
      <GlobalNavbar />
      {children}
    </div>
  )
}
