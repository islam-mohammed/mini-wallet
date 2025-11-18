import type { Channel } from 'laravel-echo'
import { echo } from './echo'

export interface WalletUser {
  id: number
  name: string
  username: string
  email?: string
  balance?: string
}

export interface WalletTransaction {
  id: string
  sender_id: number
  receiver_id: number
  amount: string
  commission_fee: string
  direction?: 'incoming' | 'outgoing' | null
  created_at: string | null
  sender?: WalletUser
  receiver?: WalletUser
}

export interface TransactionCreatedPayload {
  transaction: WalletTransaction,
  sender_balance: string
  receiver_balance: string
}

