import type { Config } from 'jest'
import nextJest from 'next/jest'

const createJestConfig = nextJest({
  dir: './',
})

const customJestConfig: Config = {
  clearMocks: true,
  collectCoverage: true,
  coverageDirectory: 'coverage',
  moduleNameMapper: {
    '@/atoms/(.*)': ['<rootDir>/src/atoms/$1'],
    '@/app/(.*)': ['<rootDir>/src/app/$1'],
    '@/icons/(.*)': ['<rootDir>/src/icons/$1'],
    '@/components/(.*)': ['<rootDir>/src/components/$1'],
    '@/styles/(.*)': ['<rootDir>/src/styles/$1'],
    '@/utils/(.*)': ['<rootDir>/src/utils/$1'],
    '@/data/(.*)': ['<rootDir>/src/data/$1'],
    '@/server/(.*)': ['<rootDir>/src/server/$1'],
    '@/server-rsc/(.*)': ['<rootDir>/src/server-rsc/$1'],
    '@/pages/(.*)': ['<rootDir>/src/pages/$1'],
    '@/content/(.*)': ['<rootDir>/src/content/$1'],
  },
  setupFilesAfterEnv: ['<rootDir>/jest.setup.ts'],
  testEnvironment: 'jest-environment-jsdom',
}

export default createJestConfig(customJestConfig)
