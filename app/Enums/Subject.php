<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Subject extends Enum
{
    const All = 'all';

    const Admins = 'admins';

    const Roles = 'roles';

    const Permissions = 'permissions';

    const FinancingOrders = 'financingOrders';

    const LenderWallet = 'lenderWallet';

    const LenderFinancingOrderCost = 'lenderFinancingOrderCost';

    const LenderUsers = 'lenderUsers';

    const LenderSettings = 'lenderSettings';

    const LenderTransactions = 'lenderTransactions';

    const Dashboard = 'dashboard';

    const LenderEdaatInvoices = 'lenderEdaatInvoices';

    const Lenders = 'lenders';

    const Enquiries = 'enquiries';

    const EnquiryReplies = 'enquiryReplies';

    const LenderWebhookSecret = 'lenderWebhookSecret';

    const LenderWebhooks = 'lenderWebhooks';

    const WakalaTemplates = 'wakalaTemplates';

    const LenderAreaSettings = 'lenderAreaSettings';

    const ProjectSettings = 'projectSettings';

    const TraderUsers = 'traderUsers';

    const Traders = 'traders';

    const TraderUserInvitation = 'traderUserInvitation';

    const WalletNotifications = 'walletNotifications';

    const CommodityMarket = 'commodityMarket';

    const CommodityMarketSuppliers = 'commodityMarketSuppliers';

    const CommodityMarketCommodityTypes = 'commodityMarketCommodityTypes';

    const CommodityMarketCommodityItems = 'commodityMarketCommodityItems';

    const CommoditySupplierUsers = 'commoditySupplierUsers';

}
