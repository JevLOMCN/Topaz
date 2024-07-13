import ConquestsBackground from '@/components/global/ConquestsBackground'
import GlobalNavbar from '@/components/global/Navbar'
import { RouteMetadata } from '../DefaultMetadata'

export const metadata = RouteMetadata.Conquests

export default function Layout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex h-full min-h-screen w-full flex-col font-main">
      <ConquestsBackground />
      <GlobalNavbar />
      {children}
    </div>
  )
}
